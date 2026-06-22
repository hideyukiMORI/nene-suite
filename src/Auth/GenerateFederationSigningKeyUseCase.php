<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Generates the federation IdP signing key (milestone B1.5): mints an ES256 key pair, stores the
 * **public** JWK as the single `active` key, and returns the private key for the operator to place
 * out of band (it never touches the DB). In one transaction (ADR 0007 §5) the row is saved and a
 * `federation_signing_key.generated` audit row is recorded on the same connection. Enforces the
 * exactly-one-active invariant — replacing an active key is rotation (B1.8), not a second generate.
 */
final readonly class GenerateFederationSigningKeyUseCase implements GenerateFederationSigningKeyUseCaseInterface
{
    public function __construct(
        private FederationKeyGenerator $generator,
        private DatabaseTransactionManagerInterface $transactions,
        private FederationSigningKeyRepositoryFactoryInterface $keys,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(GenerateFederationSigningKeyInput $input): GenerateFederationSigningKeyOutput
    {
        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): GenerateFederationSigningKeyOutput {
                $keys = $this->keys->create($query);

                // Exactly-one-active is enforced here in application code: this is an operator-run,
                // run-once CLI (no concurrent callers), and a partial unique index on status='active'
                // is not portable across MySQL/SQLite.
                if ($keys->findActive() !== null) {
                    throw new ActiveFederationSigningKeyExistsException();
                }

                $generated = $this->generator->generate();
                $audit = $this->audit->create($query);
                $now = gmdate('Y-m-d\TH:i:s\Z');

                $key = new FederationSigningKey(
                    id: (string) new Ulid(),
                    kid: $generated->kid,
                    alg: $generated->alg,
                    publicJwk: $generated->publicJwk,
                    status: FederationSigningKeyStatus::Active,
                    createdAt: $now,
                    activatedAt: $now,
                    retiredAt: null,
                );

                $keys->save($key);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'federation_signing_key.generated',
                    entityType: 'federation_signing_key',
                    entityId: $key->id,
                    beforeJson: null,
                    afterJson: ['kid' => $key->kid, 'alg' => $key->alg, 'status' => $key->status->value],
                    source: 'system',
                    requestId: $input->requestId,
                ));

                return new GenerateFederationSigningKeyOutput($generated->privateKeyPem, $key->kid, $generated->publicJwk);
            },
        );
    }
}

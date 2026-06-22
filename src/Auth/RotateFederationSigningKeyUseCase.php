<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Rotates the federation signing key (milestone B1.8): the current active key is demoted to
 * `retiring` (kept in the JWKS so in-flight assertions still verify during the grace window), a new
 * key is minted as `active`, and any previously-`retiring` key is `retired` (dropped from JWKS) — so
 * the published set stays bounded to one active + one retiring. The new private key is returned for
 * the operator to install; only the public JWK is persisted. Emits `federation_signing_key.rotated`
 * atomically with the transition (ADR 0007 §5).
 *
 * Operator-run (CLI), never on boot — rotating per restart would churn the `kid` faster than
 * siblings' JWKS caches refresh.
 */
final readonly class RotateFederationSigningKeyUseCase implements RotateFederationSigningKeyUseCaseInterface
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

                $active = $keys->findActive();

                if ($active === null) {
                    throw new NoActiveFederationSigningKeyException();
                }

                $audit = $this->audit->create($query);
                $now = gmdate('Y-m-d\TH:i:s\Z');

                // Retire any already-retiring key (its grace window has long passed by the next
                // rotation), then demote the current active to retiring.
                foreach ($keys->findByStatus(FederationSigningKeyStatus::Retiring) as $retiring) {
                    $keys->updateStatus($retiring->id, FederationSigningKeyStatus::Retired, $now);
                }

                $keys->updateStatus($active->id, FederationSigningKeyStatus::Retiring, null);

                $generated = $this->generator->generate();
                $newKey = new FederationSigningKey(
                    id: (string) new Ulid(),
                    kid: $generated->kid,
                    alg: $generated->alg,
                    publicJwk: $generated->publicJwk,
                    status: FederationSigningKeyStatus::Active,
                    createdAt: $now,
                    activatedAt: $now,
                    retiredAt: null,
                );

                $keys->save($newKey);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'federation_signing_key.rotated',
                    entityType: 'federation_signing_key',
                    entityId: $newKey->id,
                    beforeJson: ['kid' => $active->kid, 'status' => FederationSigningKeyStatus::Active->value],
                    afterJson: ['kid' => $newKey->kid, 'status' => FederationSigningKeyStatus::Active->value],
                    source: 'system',
                    requestId: $input->requestId,
                ));

                return new GenerateFederationSigningKeyOutput($generated->privateKeyPem, $newKey->kid, $generated->publicJwk);
            },
        );
    }
}

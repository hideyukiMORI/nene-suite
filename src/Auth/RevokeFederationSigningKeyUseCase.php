<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;

/**
 * Emergency-revokes a federation signing key by kid (milestone B1.8): sets it `revoked`, which drops
 * it from the published JWKS immediately and makes the verifier reject any assertion bearing that
 * kid. Used on key compromise. Emits `federation_signing_key.revoked` atomically (ADR 0007 §5).
 *
 * Caveat (documented in the runbook): siblings cache the JWKS and refresh on an UNKNOWN kid, so a
 * revoked-but-still-cached kid keeps verifying at a sibling until its JWKS cache expires — the real
 * blast-radius window is the JWKS cache max-age, not the revoke instant.
 */
final readonly class RevokeFederationSigningKeyUseCase implements RevokeFederationSigningKeyUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private FederationSigningKeyRepositoryFactoryInterface $keys,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(RevokeFederationSigningKeyInput $input): bool
    {
        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): bool {
                $keys = $this->keys->create($query);

                $key = $keys->findByKid($input->kid);

                if ($key === null) {
                    throw new FederationSigningKeyNotFoundException($input->kid);
                }

                if ($key->status === FederationSigningKeyStatus::Revoked) {
                    return false;
                }

                $now = gmdate('Y-m-d\TH:i:s\Z');
                $keys->updateStatus($key->id, FederationSigningKeyStatus::Revoked, $now);

                $audit = $this->audit->create($query);
                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'federation_signing_key.revoked',
                    entityType: 'federation_signing_key',
                    entityId: $key->id,
                    beforeJson: ['kid' => $key->kid, 'status' => $key->status->value],
                    afterJson: ['kid' => $key->kid, 'status' => FederationSigningKeyStatus::Revoked->value],
                    source: 'system',
                    requestId: $input->requestId,
                ));

                return true;
            },
        );
    }
}

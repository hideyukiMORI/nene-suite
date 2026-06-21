<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;

/**
 * Revokes (removes) a membership. The platform must retain at least one `superadmin`
 * and an organization at least one `admin`, so revoking the last of either is rejected.
 * The membership row is hard-deleted; the append-only `membership.revoked` audit row is
 * the permanent record (audit-trail §7). In a single transaction (ADR 0007 §5) the row
 * is deleted and the event recorded. No HTTP surface yet (milestone A3).
 */
final readonly class RevokeMembershipUseCase implements RevokeMembershipUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private MembershipRepositoryFactoryInterface $memberships,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(RevokeMembershipInput $input): RevokeMembershipOutput
    {
        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): RevokeMembershipOutput {
                $memberships = $this->memberships->create($query);
                $audit = $this->audit->create($query);

                $existing = $memberships->findById($input->membershipId);

                if ($existing === null) {
                    throw new MembershipNotFoundException($input->membershipId);
                }

                $this->guardLastPrivileged($memberships, $existing);

                $memberships->delete($existing->id);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'membership.revoked',
                    entityType: 'membership',
                    entityId: $existing->id,
                    beforeJson: MembershipView::toArray($existing),
                    afterJson: null,
                    source: 'system',
                    requestId: $input->requestId,
                ));

                return new RevokeMembershipOutput($existing);
            },
        );
    }

    private function guardLastPrivileged(MembershipRepositoryInterface $memberships, Membership $membership): void
    {
        if ($membership->role === Role::Superadmin) {
            if (count($memberships->findPlatformMemberships()) <= 1) {
                throw new MembershipInvariantException('Cannot revoke the last platform superadmin.');
            }

            return;
        }

        if ($membership->role === Role::Admin && $membership->organizationId !== null) {
            $admins = array_filter(
                $memberships->findByOrganization($membership->organizationId),
                static fn (Membership $candidate): bool => $candidate->role === Role::Admin,
            );

            if (count($admins) <= 1) {
                throw new MembershipInvariantException(
                    "Organization '{$membership->organizationId}' must retain at least one admin; its last admin cannot be revoked.",
                );
            }
        }
    }
}

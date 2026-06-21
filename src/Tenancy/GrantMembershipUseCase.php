<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Grants an operator a membership: a platform `superadmin` (no organization) or an
 * organization-scoped `admin` / `member` / `viewer`. Enforces role↔scope consistency
 * (ADR 0012 role model) and one membership per (operator, organization) — including a
 * single platform membership per operator, since SQL treats NULL `organization_id` as
 * distinct so the uniqueness must be enforced here. In a single transaction
 * (ADR 0007 §5) the row is saved and `membership.granted` is recorded. No HTTP surface
 * yet (milestone A3); referential existence of the operator/organization is the
 * caller's responsibility (A4 installer / A7 console).
 */
final readonly class GrantMembershipUseCase implements GrantMembershipUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private MembershipRepositoryFactoryInterface $memberships,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(GrantMembershipInput $input): GrantMembershipOutput
    {
        $this->validateScope($input->role, $input->organizationId);

        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): GrantMembershipOutput {
                $memberships = $this->memberships->create($query);
                $audit = $this->audit->create($query);

                if ($memberships->findByOperatorAndOrganization($input->operatorId, $input->organizationId) !== null) {
                    throw new MembershipConflictException($input->operatorId, $input->organizationId);
                }

                $now = gmdate('Y-m-d\TH:i:s\Z');
                $membership = new Membership(
                    id: (string) new Ulid(),
                    operatorId: $input->operatorId,
                    organizationId: $input->organizationId,
                    role: $input->role,
                    createdAt: $now,
                    updatedAt: $now,
                );

                $memberships->save($membership);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'membership.granted',
                    entityType: 'membership',
                    entityId: $membership->id,
                    beforeJson: null,
                    afterJson: MembershipView::toArray($membership),
                    source: 'system',
                    requestId: $input->requestId,
                ));

                return new GrantMembershipOutput($membership);
            },
        );
    }

    private function validateScope(Role $role, ?string $organizationId): void
    {
        if ($role->requiresOrganization() && $organizationId === null) {
            throw new MembershipValidationException(
                'organizationId',
                "Role '{$role->value}' must be scoped to an organization.",
            );
        }

        if ($role->isPlatform() && $organizationId !== null) {
            throw new MembershipValidationException(
                'organizationId',
                "A platform '{$role->value}' membership must not be scoped to an organization.",
            );
        }
    }
}

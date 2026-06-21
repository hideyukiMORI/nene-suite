<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;

/**
 * Changes a membership's role within its existing scope. A platform `superadmin`
 * membership cannot be re-scoped (its role may only stay `superadmin`), and an
 * organization membership may only move among `admin` / `member` / `viewer`. An
 * organization must retain at least one `admin`, so demoting its last admin is
 * rejected. A no-op (same role) records nothing. In a single transaction
 * (ADR 0007 §5) the role is updated and `membership.role_changed` is recorded. No HTTP
 * surface yet (milestone A3).
 */
final readonly class ChangeMembershipRoleUseCase implements ChangeMembershipRoleUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private MembershipRepositoryFactoryInterface $memberships,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(ChangeMembershipRoleInput $input): ChangeMembershipRoleOutput
    {
        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): ChangeMembershipRoleOutput {
                $memberships = $this->memberships->create($query);
                $audit = $this->audit->create($query);

                $existing = $memberships->findById($input->membershipId);

                if ($existing === null) {
                    throw new MembershipNotFoundException($input->membershipId);
                }

                $this->validateScope($input->role, $existing->organizationId);

                if ($existing->role === $input->role) {
                    return new ChangeMembershipRoleOutput($existing);
                }

                if ($existing->role === Role::Admin && $input->role !== Role::Admin) {
                    $this->guardLastAdmin($memberships, $existing);
                }

                $updated = new Membership(
                    id: $existing->id,
                    operatorId: $existing->operatorId,
                    organizationId: $existing->organizationId,
                    role: $input->role,
                    createdAt: $existing->createdAt,
                    updatedAt: gmdate('Y-m-d\TH:i:s\Z'),
                );

                $memberships->updateRole($updated);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'membership.role_changed',
                    entityType: 'membership',
                    entityId: $updated->id,
                    beforeJson: ['role' => $existing->role->value],
                    afterJson: ['role' => $updated->role->value],
                    source: 'system',
                    requestId: $input->requestId,
                ));

                return new ChangeMembershipRoleOutput($updated);
            },
        );
    }

    private function validateScope(Role $role, ?string $organizationId): void
    {
        if ($role->requiresOrganization() && $organizationId === null) {
            throw new MembershipValidationException(
                'role',
                "A platform membership cannot take the organization-scoped role '{$role->value}'.",
            );
        }

        if ($role->isPlatform() && $organizationId !== null) {
            throw new MembershipValidationException(
                'role',
                "An organization membership cannot take the platform role '{$role->value}'.",
            );
        }
    }

    private function guardLastAdmin(MembershipRepositoryInterface $memberships, Membership $membership): void
    {
        $organizationId = $membership->organizationId;

        if ($organizationId === null) {
            return;
        }

        $admins = array_filter(
            $memberships->findByOrganization($organizationId),
            static fn (Membership $candidate): bool => $candidate->role === Role::Admin,
        );

        if (count($admins) <= 1) {
            throw new MembershipInvariantException(
                "Organization '{$organizationId}' must retain at least one admin; its last admin cannot be demoted.",
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;

/**
 * Soft-disables an organization. Organizations are never hard-deleted when issued
 * documents may exist in a sibling app (ADR 0012 §5 / §11); this only flips the
 * status to `Disabled`. In a single transaction (ADR 0007 §5) the row is updated and
 * `organization.disabled` is recorded with before/after snapshots. Already-disabled is
 * an idempotent no-op (no change, no audit row). No HTTP surface yet (milestone A2).
 */
final readonly class DisableOrganizationUseCase implements DisableOrganizationUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private OrganizationRepositoryFactoryInterface $organizations,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(DisableOrganizationInput $input): DisableOrganizationOutput
    {
        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): DisableOrganizationOutput {
                $organizations = $this->organizations->create($query);
                $audit = $this->audit->create($query);

                $existing = $organizations->findById($input->organizationId);

                if ($existing === null) {
                    throw new OrganizationNotFoundException($input->organizationId);
                }

                if ($existing->status === OrganizationStatus::Disabled) {
                    return new DisableOrganizationOutput($existing);
                }

                $disabled = new Organization(
                    id: $existing->id,
                    externalId: $existing->externalId,
                    name: $existing->name,
                    slug: $existing->slug,
                    status: OrganizationStatus::Disabled,
                    createdAt: $existing->createdAt,
                    updatedAt: gmdate('Y-m-d\TH:i:s\Z'),
                );

                $organizations->update($disabled);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'organization.disabled',
                    entityType: 'organization',
                    entityId: $disabled->id,
                    beforeJson: OrganizationView::toArray($existing),
                    afterJson: OrganizationView::toArray($disabled),
                    source: 'system',
                    orgExternalId: $disabled->externalId,
                    requestId: $input->requestId,
                ));

                return new DisableOrganizationOutput($disabled);
            },
        );
    }
}

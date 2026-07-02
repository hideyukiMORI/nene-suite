<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;

/**
 * Re-enables a soft-disabled organization — the reverse of
 * {@see DisableOrganizationUseCase}. Disable is a reversible freeze (ADR 0012
 * §5 / §11), so enabling only flips the status back to `Active`; frozen data is
 * untouched. In a single transaction (ADR 0007 §5) the row is updated and
 * `organization.enabled` is recorded with before/after snapshots.
 * Already-active is an idempotent no-op (no change, no audit row).
 */
final readonly class EnableOrganizationUseCase implements EnableOrganizationUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private OrganizationRepositoryFactoryInterface $organizations,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(EnableOrganizationInput $input): EnableOrganizationOutput
    {
        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): EnableOrganizationOutput {
                $organizations = $this->organizations->create($query);
                $audit = $this->audit->create($query);

                $existing = $organizations->findById($input->organizationId);

                if ($existing === null) {
                    throw new OrganizationNotFoundException($input->organizationId);
                }

                if ($existing->status === OrganizationStatus::Active) {
                    return new EnableOrganizationOutput($existing);
                }

                $enabled = new Organization(
                    id: $existing->id,
                    externalId: $existing->externalId,
                    name: $existing->name,
                    slug: $existing->slug,
                    status: OrganizationStatus::Active,
                    createdAt: $existing->createdAt,
                    updatedAt: gmdate('Y-m-d\TH:i:s\Z'),
                );

                $organizations->update($enabled);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'organization.enabled',
                    entityType: 'organization',
                    entityId: $enabled->id,
                    beforeJson: OrganizationView::toArray($existing),
                    afterJson: OrganizationView::toArray($enabled),
                    source: 'system',
                    orgExternalId: $enabled->externalId,
                    requestId: $input->requestId,
                ));

                return new EnableOrganizationOutput($enabled);
            },
        );
    }
}

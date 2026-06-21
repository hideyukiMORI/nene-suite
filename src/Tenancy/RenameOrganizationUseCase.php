<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;

/**
 * Renames an organization's display name. Identity only — supersedes the legacy
 * `org_display_name.changed` / `suite_org_profile` audit path (audit-trail §4). In a
 * single transaction (ADR 0007 §5) the row is updated and `organization.renamed` is
 * recorded with the before/after name. A no-op rename (same name) makes no change and
 * records nothing. No HTTP surface yet (milestone A2).
 */
final readonly class RenameOrganizationUseCase implements RenameOrganizationUseCaseInterface
{
    private const NAME_MAX_LENGTH = 320;

    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private OrganizationRepositoryFactoryInterface $organizations,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(RenameOrganizationInput $input): RenameOrganizationOutput
    {
        $name = $this->validateName($input->name);

        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($name, $input): RenameOrganizationOutput {
                $organizations = $this->organizations->create($query);
                $audit = $this->audit->create($query);

                $existing = $organizations->findById($input->organizationId);

                if ($existing === null) {
                    throw new OrganizationNotFoundException($input->organizationId);
                }

                if ($existing->name === $name) {
                    return new RenameOrganizationOutput($existing);
                }

                $renamed = new Organization(
                    id: $existing->id,
                    externalId: $existing->externalId,
                    name: $name,
                    slug: $existing->slug,
                    status: $existing->status,
                    createdAt: $existing->createdAt,
                    updatedAt: gmdate('Y-m-d\TH:i:s\Z'),
                );

                $organizations->update($renamed);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'organization.renamed',
                    entityType: 'organization',
                    entityId: $renamed->id,
                    beforeJson: ['name' => $existing->name],
                    afterJson: ['name' => $renamed->name],
                    source: 'system',
                    orgExternalId: $renamed->externalId,
                    requestId: $input->requestId,
                ));

                return new RenameOrganizationOutput($renamed);
            },
        );
    }

    private function validateName(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            throw new OrganizationValidationException('name', 'A non-empty organization name is required.');
        }

        if (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            throw new OrganizationValidationException('name', 'Name must be at most ' . self::NAME_MAX_LENGTH . ' characters.');
        }

        return $name;
    }
}

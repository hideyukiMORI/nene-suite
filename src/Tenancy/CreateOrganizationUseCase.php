<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Registers a new organization in the Suite roster. Mints the internal `id` and a
 * distinct, stable `external_id` (the ADR 0012 federation key propagated to sibling
 * apps), validates name + slug, enforces slug uniqueness, and — in a single
 * transaction (ADR 0007 §5) — saves the row and records `organization.created`.
 *
 * No HTTP surface yet (milestone A2); driven by the installer bootstrap (A4) and the
 * superadmin console (A7). The Suite stores roster/identity only, never sibling
 * domain data (ADR 0012 §11).
 */
final readonly class CreateOrganizationUseCase implements CreateOrganizationUseCaseInterface
{
    private const NAME_MAX_LENGTH = 320;
    private const SLUG_MAX_LENGTH = 160;
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private OrganizationRepositoryFactoryInterface $organizations,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(CreateOrganizationInput $input): CreateOrganizationOutput
    {
        $name = $this->validateName($input->name);
        $slug = $this->validateSlug($input->slug);

        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($name, $slug, $input): CreateOrganizationOutput {
                $organizations = $this->organizations->create($query);
                $audit = $this->audit->create($query);

                if ($organizations->findBySlug($slug) !== null) {
                    throw new OrganizationSlugConflictException($slug);
                }

                $now = gmdate('Y-m-d\TH:i:s\Z');
                $organization = new Organization(
                    id: (string) new Ulid(),
                    externalId: $this->resolveExternalId($input->externalId),
                    name: $name,
                    slug: $slug,
                    status: OrganizationStatus::Active,
                    createdAt: $now,
                    updatedAt: $now,
                );

                $organizations->save($organization);

                $audit->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'organization.created',
                    entityType: 'organization',
                    entityId: $organization->id,
                    beforeJson: null,
                    afterJson: OrganizationView::toArray($organization),
                    source: 'system',
                    orgExternalId: $organization->externalId,
                    requestId: $input->requestId,
                ));

                return new CreateOrganizationOutput($organization);
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

    private function validateSlug(string $slug): string
    {
        if (mb_strlen($slug) > self::SLUG_MAX_LENGTH) {
            throw new OrganizationValidationException('slug', 'Slug must be at most ' . self::SLUG_MAX_LENGTH . ' characters.');
        }

        if (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            throw new OrganizationValidationException(
                'slug',
                'Slug must be lowercase alphanumeric segments separated by single hyphens (e.g. "acme-kk").',
            );
        }

        return $slug;
    }

    /**
     * Resolves the federation key (`external_id`): a valid ULID seed is canonicalised
     * (uppercase Crockford base32, matching minted rows and `findByExternalId` lookups);
     * null or a malformed seed falls open to a freshly minted ULID so a misconfigured
     * `NENE_SUITE_ORG_EXTERNAL_ID` never aborts organization creation.
     */
    private function resolveExternalId(?string $provided): string
    {
        if ($provided !== null && Ulid::isValid($provided)) {
            return (new Ulid($provided))->toBase32();
        }

        return (string) new Ulid();
    }
}

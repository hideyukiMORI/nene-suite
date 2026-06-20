<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * A tenant in the Suite registry.
 *
 * The Suite holds the organization roster + identity only — never sibling domain
 * data (ADR 0012 §11). `externalId` is the federation UUID (`org_external_id`)
 * propagated to sibling apps, where it links to `organizations.external_id`.
 * `slug` drives per-organization resolution (path / subdomain) in suite mode.
 */
final readonly class Organization
{
    public function __construct(
        public string $id,
        public string $externalId,
        public string $name,
        public string $slug,
        public OrganizationStatus $status,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}

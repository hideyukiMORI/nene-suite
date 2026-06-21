<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * camelCase Organization representation for audit snapshots (and, later, HTTP).
 * The Suite holds roster/identity only — never sibling domain data (ADR 0012 §11).
 */
final readonly class OrganizationView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'externalId' => $organization->externalId,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'status' => $organization->status->value,
        ];
    }
}

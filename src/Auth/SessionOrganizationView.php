<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final class SessionOrganizationView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(SessionOrganization $organization): array
    {
        return [
            'organizationId' => $organization->organizationId,
            'externalId' => $organization->externalId,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'role' => $organization->role->value,
        ];
    }
}

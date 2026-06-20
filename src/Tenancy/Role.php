<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * Operator role within the Suite, mirroring the sibling apps' hierarchy
 * (NeNe Invoice ADR 0006 / NeNe Records): a platform `Superadmin` operates
 * across organizations, while `Admin` / `Member` / `Viewer` are scoped to one.
 */
enum Role: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    /**
     * Platform-level, cross-organization role. A membership in this role has no
     * organization (`organization_id` is null).
     */
    public function isPlatform(): bool
    {
        return $this === self::Superadmin;
    }

    /**
     * Whether a membership in this role must be scoped to an organization.
     */
    public function requiresOrganization(): bool
    {
        return !$this->isPlatform();
    }
}

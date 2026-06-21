<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * Parses the request-side organization role (`admin`/`member`/`viewer`) for the membership HTTP
 * endpoints. Rejects unknown values and the platform `superadmin` role — which is not grantable
 * through the org-scoped console — with a 422 {@see MembershipValidationException}.
 */
final class MembershipRoleParser
{
    public static function parse(mixed $raw): Role
    {
        $value = is_string($raw) ? trim($raw) : '';
        $role = Role::tryFrom($value);

        if ($role === null || $role === Role::Superadmin) {
            throw new MembershipValidationException('role', 'role must be one of: admin, member, viewer.');
        }

        return $role;
    }
}

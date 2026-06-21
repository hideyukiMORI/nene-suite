<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * camelCase Membership representation for audit snapshots (and, later, HTTP).
 */
final readonly class MembershipView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Membership $membership): array
    {
        return [
            'id' => $membership->id,
            'operatorId' => $membership->operatorId,
            'organizationId' => $membership->organizationId,
            'role' => $membership->role->value,
        ];
    }
}

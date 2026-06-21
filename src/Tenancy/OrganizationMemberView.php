<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final class OrganizationMemberView
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(OrganizationMember $member): array
    {
        return [
            'membershipId' => $member->membershipId,
            'operatorId' => $member->operatorId,
            'email' => $member->email,
            'displayName' => $member->displayName,
            'role' => $member->role->value,
        ];
    }
}

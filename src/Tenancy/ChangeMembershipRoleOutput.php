<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class ChangeMembershipRoleOutput
{
    public function __construct(
        public Membership $membership,
    ) {
    }
}

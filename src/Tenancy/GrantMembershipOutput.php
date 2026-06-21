<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class GrantMembershipOutput
{
    public function __construct(
        public Membership $membership,
    ) {
    }
}

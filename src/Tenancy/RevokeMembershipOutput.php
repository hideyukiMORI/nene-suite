<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class RevokeMembershipOutput
{
    public function __construct(
        public Membership $membership,
    ) {
    }
}

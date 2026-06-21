<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class RevokeMembershipInput
{
    public function __construct(
        public string $membershipId,
        public ?string $requestId = null,
    ) {
    }
}

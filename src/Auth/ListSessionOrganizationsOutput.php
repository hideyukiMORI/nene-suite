<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

final readonly class ListSessionOrganizationsOutput
{
    /**
     * @param list<SessionOrganization> $organizations
     */
    public function __construct(
        public array $organizations,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseTargets;

use NeNeSuite\InstallSession\InstallSession;

final readonly class SetDatabaseTargetsOutput
{
    public function __construct(
        public InstallSession $session,
    ) {
    }
}

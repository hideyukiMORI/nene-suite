<?php

declare(strict_types=1);

namespace NeNeSuite\AppSelection;

use NeNeSuite\InstallSession\InstallSession;

final readonly class UpdateAppSelectionOutput
{
    public function __construct(
        public InstallSession $session,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class StartInstallSessionOutput
{
    public function __construct(
        public InstallSession $session,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class FailInstallSessionOutput
{
    public function __construct(
        public InstallSession $session,
    ) {
    }
}

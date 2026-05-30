<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class CompleteInstallSessionOutput
{
    public function __construct(
        public InstallSession $session,
    ) {
    }
}

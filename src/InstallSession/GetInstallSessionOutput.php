<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class GetInstallSessionOutput
{
    public function __construct(
        public InstallSession $session,
    ) {
    }
}

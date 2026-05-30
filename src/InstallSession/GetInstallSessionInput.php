<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class GetInstallSessionInput
{
    public function __construct(
        public string $installSessionId,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class AcceptDisclaimerOutput
{
    public function __construct(
        public InstallSession $session,
    ) {
    }
}

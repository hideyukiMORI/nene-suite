<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseTargets;

use NeNeSuite\InstallSession\AppDatabaseTargetSelection;

final readonly class SetDatabaseTargetsInput
{
    /**
     * @param list<AppDatabaseTargetSelection> $targets per-app database target overrides for apps in the session's selection
     */
    public function __construct(
        public string $installSessionId,
        public array $targets,
        public ?string $requestId = null,
    ) {
    }
}

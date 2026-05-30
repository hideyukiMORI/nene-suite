<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class StartInstallSessionInput
{
    /**
     * @param list<string> $selectedApps initial selection (dependency resolution
     *                                    happens later via updateAppSelection)
     */
    public function __construct(
        public InstallTier $tier,
        public array $selectedApps = [],
        public ?string $orgDisplayName = null,
        public ?string $requestId = null,
    ) {
    }
}

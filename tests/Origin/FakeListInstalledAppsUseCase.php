<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\InstalledApps\ListInstalledAppsOutput;
use NeNeSuite\InstalledApps\ListInstalledAppsUseCaseInterface;

/**
 * Returns a fixed installed-apps roster — lets the Origin updates use case be tested without an
 * install session / database.
 */
final readonly class FakeListInstalledAppsUseCase implements ListInstalledAppsUseCaseInterface
{
    public function __construct(
        private ListInstalledAppsOutput $output,
    ) {
    }

    public function execute(): ListInstalledAppsOutput
    {
        return $this->output;
    }
}

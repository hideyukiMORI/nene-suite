<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

interface SuiteAppUrlReaderInterface
{
    /**
     * Public base URL for an installed sibling app, or null when not configured.
     */
    public function publicUrl(string $catalogId): ?string;
}

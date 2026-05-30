<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstalledApps;

use NeNeSuite\SuiteEnv\SuiteAppUrlReaderInterface;

final readonly class FixedSuiteAppUrlReader implements SuiteAppUrlReaderInterface
{
    /**
     * @param array<string, string> $urls catalog id => public URL
     */
    public function __construct(
        private array $urls,
    ) {
    }

    public function publicUrl(string $catalogId): ?string
    {
        return $this->urls[$catalogId] ?? null;
    }
}

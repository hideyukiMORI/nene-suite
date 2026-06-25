<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SiblingHealth;

use NeNeSuite\SiblingHealth\InstalledVersionRepositoryInterface;

/**
 * In-memory {@see InstalledVersionRepositoryInterface} for tests — same last-write-wins semantics as
 * the PDO store, without a database.
 */
final class InMemoryInstalledVersionRepository implements InstalledVersionRepositoryInterface
{
    /** @var array<string, string> */
    private array $versions = [];

    public function current(string $catalogId): ?string
    {
        return $this->versions[$catalogId] ?? null;
    }

    public function record(string $catalogId, string $version, string $now): void
    {
        $this->versions[$catalogId] = $version;
    }
}

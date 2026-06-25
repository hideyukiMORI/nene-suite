<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SiblingHealth;

use NeNeSuite\SiblingHealth\SiblingHealthClientInterface;

/**
 * Returns a fixed version per public URL — lets the resolver / Origin updates use case be tested
 * without an HTTP probe. An unmapped URL (or a null mapping) behaves like an unreachable sibling.
 */
final readonly class FakeSiblingHealthClient implements SiblingHealthClientInterface
{
    /** @param array<string, ?string> $versionsByUrl */
    public function __construct(private array $versionsByUrl)
    {
    }

    public function fetchVersion(string $publicUrl): ?string
    {
        return $this->versionsByUrl[$publicUrl] ?? null;
    }
}

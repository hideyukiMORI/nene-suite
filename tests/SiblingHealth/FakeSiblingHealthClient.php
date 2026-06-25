<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SiblingHealth;

use NeNeSuite\SiblingHealth\SiblingHealthClientInterface;

/**
 * Returns a fixed version per public URL — lets the resolver / Origin updates use case be tested
 * without an HTTP probe. Mirrors the auth-gated endpoint: a null/blank machine key yields null
 * (no credential -> no data). Records the key received per URL so tests can assert it was passed.
 */
final class FakeSiblingHealthClient implements SiblingHealthClientInterface
{
    /** @var array<string, ?string> publicUrl => last machine key received */
    public array $receivedKeys = [];

    /** @param array<string, ?string> $versionsByUrl */
    public function __construct(private readonly array $versionsByUrl)
    {
    }

    public function fetchVersion(string $publicUrl, ?string $machineApiKey): ?string
    {
        $this->receivedKeys[$publicUrl] = $machineApiKey;

        if ($machineApiKey === null || $machineApiKey === '') {
            return null;
        }

        return $this->versionsByUrl[$publicUrl] ?? null;
    }
}

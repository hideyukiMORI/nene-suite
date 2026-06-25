<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SiblingHealth;

use NeNeSuite\SuiteEnv\SuiteAppMachineKeyReaderInterface;

/**
 * Returns a fixed machine API key per catalog id — lets the resolver be tested without the suite
 * environment. An unmapped catalog id (or a null mapping) behaves like an unconfigured key.
 */
final readonly class FakeSuiteAppMachineKeyReader implements SuiteAppMachineKeyReaderInterface
{
    /** @param array<string, ?string> $keysByCatalogId */
    public function __construct(private array $keysByCatalogId)
    {
    }

    public function machineApiKey(string $catalogId): ?string
    {
        return $this->keysByCatalogId[$catalogId] ?? null;
    }
}

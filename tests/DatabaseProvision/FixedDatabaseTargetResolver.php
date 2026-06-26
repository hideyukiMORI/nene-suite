<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use NeNeSuite\DatabaseProvision\DatabaseTarget;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use NeNeSuite\DatabaseProvision\DatabaseTargetResolverInterface;

/**
 * Deterministic resolver test double: returns a configured target per catalog id, and
 * the default provision target (suite server, catalog-id name) for any other id.
 */
final class FixedDatabaseTargetResolver implements DatabaseTargetResolverInterface
{
    private AppDatabaseNamer $namer;

    /** @var array<string, DatabaseTarget> */
    private array $overrides = [];

    public function __construct()
    {
        $this->namer = new AppDatabaseNamer();
    }

    public function with(DatabaseTarget $target): self
    {
        $this->overrides[$target->catalogId] = $target;

        return $this;
    }

    public function resolve(string $catalogId): DatabaseTarget
    {
        return $this->overrides[$catalogId]
            ?? new DatabaseTarget(
                $catalogId,
                DatabaseTargetMode::Provision,
                $this->namer->databaseName($catalogId),
            );
    }
}

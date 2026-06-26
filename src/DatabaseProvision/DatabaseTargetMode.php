<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

use InvalidArgumentException;

/**
 * How the suite obtains an app's database (ADR 0021 §1):
 * - `Provision` — the suite `CREATE`s the database (default; privileged connection).
 * - `Adopt` — the suite registers an existing database and never creates or mutates it.
 *
 * These are prose-stable canonical values (terminology §4); not Suite-coined wire enums beyond this.
 */
enum DatabaseTargetMode: string
{
    case Provision = 'provision';
    case Adopt = 'adopt';

    public static function fromString(string $value): self
    {
        return self::tryFrom($value)
            ?? throw new InvalidArgumentException("Unknown database target mode: {$value}");
    }
}

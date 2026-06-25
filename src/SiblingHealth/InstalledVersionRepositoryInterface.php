<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

/**
 * Persists the last-known installed version per suite-managed product (#255, epic #251 prereq).
 * Read to build the installed-version diff for the Origin update aggregator; written after a probe
 * reports a version. Last-write-wins — a version can legitimately move up (update) or down
 * (reinstall / downgrade), so there is no monotonic guard (unlike the gen watermark). Absence of a
 * row means the version is unknown.
 */
interface InstalledVersionRepositoryInterface
{
    /** The last-known installed version for `$catalogId`, or null when none is recorded. */
    public function current(string $catalogId): ?string;

    /** Upsert the last-known installed version. `$now` is the ISO-8601 UTC probe time. */
    public function record(string $catalogId, string $version, string $now): void;
}

<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

use DateTimeImmutable;
use NeNeSuite\InstalledApps\InstalledApp;

/**
 * Resolves the installed version for each suite-managed app — the input that lets the Origin update
 * aggregator diff a verified `latest` (status `unknown` → `up_to_date` / `update_available` /
 * `forced`).
 */
interface InstalledVersionResolverInterface
{
    /**
     * @param iterable<InstalledApp> $apps
     *
     * @return array<string, ?string> catalog id => installed version (null when unknown)
     */
    public function resolve(iterable $apps, DateTimeImmutable $now): array;
}

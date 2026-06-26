<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

/**
 * Resolves the per-app database target (ADR 0021). Implementations MUST return the
 * default target — `Provision`, suite server, catalog-id name — when an app carries no
 * override, so the common case is byte-identical to the suite's historical behavior.
 */
interface DatabaseTargetResolverInterface
{
    /**
     * @throws \InvalidArgumentException on an unknown mode or an unsafe database name
     * @throws ExternalProvisionNotSupportedException when `provision` is paired with an external server (ADR 0021 OQ2)
     */
    public function resolve(string $catalogId): DatabaseTarget;
}

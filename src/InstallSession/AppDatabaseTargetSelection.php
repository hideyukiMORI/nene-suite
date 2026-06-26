<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use NeNeSuite\DatabaseProvision\DatabaseTargetMode;

/**
 * An operator's per-app database target choice carried on the install session
 * (ADR 0022 mode A). This is the **raw selection** — what the operator picked in
 * the wizard / API — not yet resolved into a {@see \NeNeSuite\DatabaseProvision\DatabaseTarget}.
 * Resolution (default name, safe-name check, external-provision refusal) happens
 * later via {@see \NeNeSuite\DatabaseProvision\DatabaseTargetFactory}, so the same
 * validation guards the session path and the env path.
 *
 * Holds no secrets: `$server` is a non-secret host / label and `$name` is an
 * existing database name (adopt only). Connection credentials live in the app's
 * own `*_DB_*` env, never here.
 */
final readonly class AppDatabaseTargetSelection
{
    public function __construct(
        public string $catalogId,
        public DatabaseTargetMode $mode,
        public ?string $server = null,
        public ?string $name = null,
    ) {
    }
}

<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * ADR 0022 mode A: carry the operator's per-app database target overrides on the
 * install session. Nullable (no default) so the alter is backward-compatible on
 * upgraded hosts and portable across MySQL / PostgreSQL / SQLite (TEXT columns
 * cannot take a DEFAULT on older MySQL). NULL / `[]` = env / default targets.
 */
final class AddDatabaseTargetsToInstallSessions extends AbstractMigration
{
    public function change(): void
    {
        $this->table('install_sessions')
            ->addColumn('database_targets_json', 'text', ['null' => true])
            ->update();
    }
}

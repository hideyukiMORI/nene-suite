<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInstalledAppVersionsTable extends AbstractMigration
{
    public function change(): void
    {
        // Last-known installed version per suite-managed product, learned from the sibling `/health`
        // probe (#255, epic #251 prerequisite). Supplies the installed version to the Origin update
        // aggregator so a verified `latest` can be diffed (status unknown -> up_to_date /
        // update_available / forced) instead of surfaced latest-only. Last-write-wins (a version can
        // move up on update or down on reinstall); absence of a row means the version is unknown.
        // Cross-engine via the Phinx adapter API.
        $this->table('installed_app_versions', ['id' => false, 'primary_key' => ['catalog_id']])
            ->addColumn('catalog_id', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('version', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('checked_at', 'string', ['limit' => 32, 'null' => false])
            ->create();
    }
}

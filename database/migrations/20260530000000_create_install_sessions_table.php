<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInstallSessionsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('install_sessions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('suite_id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('tier', 'string', ['limit' => 8, 'null' => false])
            ->addColumn('catalog_revision', 'integer', ['null' => false])
            ->addColumn('selected_apps_json', 'text', ['null' => false])
            ->addColumn('disclaimer_accepted', 'boolean', ['null' => false, 'default' => false])
            ->addColumn('disclaimer_accepted_at', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('org_external_id', 'char', ['limit' => 26, 'null' => true])
            ->addColumn('org_display_name', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('install_manifest_id', 'char', ['limit' => 26, 'null' => true])
            ->addColumn('failure_code', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('completed_at', 'string', ['limit' => 32, 'null' => true])
            ->addIndex(['suite_id'])
            ->addIndex(['status'])
            ->create();
    }
}

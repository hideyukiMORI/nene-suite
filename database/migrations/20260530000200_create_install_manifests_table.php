<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateInstallManifestsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('install_manifests', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('suite_id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('content_json', 'text', ['null' => false])
            ->addColumn('content_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addIndex(['suite_id'])
            ->create();
    }
}

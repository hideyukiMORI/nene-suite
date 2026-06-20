<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrganizationsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('organizations', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('external_id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('name', 'string', ['limit' => 320, 'null' => false])
            ->addColumn('slug', 'string', ['limit' => 160, 'null' => false])
            ->addColumn('status', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->addIndex(['external_id'], ['unique' => true])
            ->addIndex(['slug'], ['unique' => true])
            ->create();
    }
}

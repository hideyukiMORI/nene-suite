<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMembershipsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('memberships', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('operator_id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('organization_id', 'char', ['limit' => 26, 'null' => true])
            ->addColumn('role', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->addIndex(['operator_id', 'organization_id'], ['unique' => true])
            ->addIndex(['organization_id'])
            ->create();
    }
}

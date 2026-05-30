<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOperatorsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('operators', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('email', 'string', ['limit' => 320, 'null' => false])
            ->addColumn('password_hash', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('display_name', 'string', ['limit' => 320, 'null' => true])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->addIndex(['email'], ['unique' => true])
            ->create();
    }
}

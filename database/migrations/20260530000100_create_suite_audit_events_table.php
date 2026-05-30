<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSuiteAuditEventsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('suite_audit_events', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('suite_id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('org_external_id', 'char', ['limit' => 26, 'null' => true])
            ->addColumn('actor_user_id', 'char', ['limit' => 26, 'null' => true])
            ->addColumn('actor_label', 'string', ['limit' => 320, 'null' => true])
            ->addColumn('action', 'string', ['limit' => 128, 'null' => false])
            ->addColumn('entity_type', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('entity_id', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('before_json', 'text', ['null' => true])
            ->addColumn('after_json', 'text', ['null' => true])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('source', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('request_id', 'string', ['limit' => 128, 'null' => true])
            ->addColumn('install_session_id', 'char', ['limit' => 26, 'null' => true])
            ->addColumn('metadata_json', 'text', ['null' => true])
            ->addIndex(['suite_id'])
            ->addIndex(['install_session_id'])
            ->addIndex(['action'])
            ->create();
    }
}

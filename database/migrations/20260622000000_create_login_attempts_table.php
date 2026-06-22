<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLoginAttemptsTable extends AbstractMigration
{
    public function change(): void
    {
        // Fixed-window login rate-limit counter (Phase B1.2). One row per rate-limit key
        // (currently only `ip:<addr>`). Not audit data; window_started_at is epoch seconds for
        // cheap, parse-free window math. The window_started_at index keeps the opportunistic GC
        // of expired rows (PdoLoginAttemptRepository::deleteExpired) cheap.
        $this->table('login_attempts', ['id' => false, 'primary_key' => ['attempt_key']])
            ->addColumn('attempt_key', 'string', ['limit' => 160, 'null' => false])
            ->addColumn('window_started_at', 'biginteger', ['null' => false])
            ->addColumn('attempt_count', 'integer', ['null' => false])
            ->addIndex(['window_started_at'])
            ->create();
    }
}

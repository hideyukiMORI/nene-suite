<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRevokedTokensTable extends AbstractMigration
{
    public function change(): void
    {
        // Apex session logout-revocation denylist (Phase B1.3). One row per revoked token `jti`;
        // expires_at is the token's own exp (epoch seconds) so rows can be reclaimed once the
        // token would have expired anyway. The expires_at index keeps the opportunistic GC cheap.
        $this->table('revoked_tokens', ['id' => false, 'primary_key' => ['jti']])
            ->addColumn('jti', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('token_type', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('expires_at', 'biginteger', ['null' => false])
            ->addColumn('revoked_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('reason', 'string', ['limit' => 64, 'null' => false])
            ->addIndex(['expires_at'])
            ->create();
    }
}

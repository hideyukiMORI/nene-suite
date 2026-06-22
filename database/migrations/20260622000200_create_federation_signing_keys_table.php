<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateFederationSigningKeysTable extends AbstractMigration
{
    public function change(): void
    {
        // Federation IdP signing keys (milestone B1.5). PUBLIC key material only (public_jwk) —
        // the private key never enters the DB (hosted edition supplies it via env/secret). `kid` is
        // the RFC 7638 JWK thumbprint. Exactly one row is `active` (enforced in the use case).
        $this->table('federation_signing_keys', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'char', ['limit' => 26, 'null' => false])
            ->addColumn('kid', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('alg', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('public_jwk', 'text', ['null' => false])
            ->addColumn('status', 'string', ['limit' => 16, 'null' => false])
            ->addColumn('created_at', 'string', ['limit' => 32, 'null' => false])
            ->addColumn('activated_at', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('retired_at', 'string', ['limit' => 32, 'null' => true])
            ->addIndex(['kid'], ['unique' => true])
            ->addIndex(['status'])
            ->create();
    }
}

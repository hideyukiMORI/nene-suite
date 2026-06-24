<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOriginGenWatermarksTable extends AbstractMigration
{
    public function change(): void
    {
        // Profiled-TUF anti-rollback watermark for the Origin read model (ADR 0017 §5). One row per
        // product slug (product-scoped, cross-channel — `gen` is the same counter across channels);
        // advanced monotonically, never regressed. Supplies the verifier's persisted_gen so a
        // replayed older `current` (lower gen) fails closed. Cross-engine via the Phinx adapter API.
        $this->table('origin_gen_watermarks', ['id' => false, 'primary_key' => ['product']])
            ->addColumn('product', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('gen', 'biginteger', ['null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->create();
    }
}

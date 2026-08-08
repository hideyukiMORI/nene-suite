<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * suite #424: re-key the anti-rollback watermark from `product` to a **tree coordinate**.
 *
 * The table was created with `PRIMARY KEY (product)`, one row per product. Origin numbers `gen`
 * independently per tree coordinate, so that single row was written by the update walk and read by
 * the feed walk: once an update at gen 42 was accepted, every feed for that product (gen 7, 3, …)
 * verified as `rollback` and failed closed. See {@see NeNeSuite\Origin\OriginGenWatermarkCoordinate}.
 *
 * Data migration is 1:1 and lossless: only the update walk ever called `record()`, so every existing
 * row is an update watermark and becomes the `update:{product}` coordinate. No feed or entitlement
 * row can exist yet, so nothing has to be split.
 *
 * Mechanism: build the new shape as a side table, copy through PHP (portable — string concatenation
 * differs across MySQL/PostgreSQL/SQLite), then drop and rename. Changing a primary key in place is
 * not portable across the engines ADR 0021 supports, and copy-then-swap keeps the old rows readable
 * until the new table is fully populated.
 */
final class RecoordinateOriginGenWatermarks extends AbstractMigration
{
    private const string TABLE = 'origin_gen_watermarks';
    private const string STAGING = 'origin_gen_watermarks_recoordinate';

    public function up(): void
    {
        $rows = $this->fetchAll(sprintf('SELECT product, gen, updated_at FROM %s', self::TABLE));

        $this->newTable(self::STAGING);

        foreach ($rows as $row) {
            $product = (string) ($row['product'] ?? '');

            if ($product === '') {
                continue;
            }

            $this->table(self::STAGING)->insert([
                'coordinate' => sprintf('update:%s', $product),
                'gen' => (int) ($row['gen'] ?? 0),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ])->saveData();
        }

        $this->table(self::TABLE)->drop()->save();
        $this->table(self::STAGING)->rename(self::TABLE)->save();
    }

    /**
     * Reverses the re-key. Only `update:` coordinates map back — feed and entitlement watermarks
     * have no representation in the product-keyed shape, so they are dropped rather than collapsed
     * onto a product row, which is exactly the defect this migration removes.
     */
    public function down(): void
    {
        $rows = $this->fetchAll(sprintf('SELECT coordinate, gen, updated_at FROM %s', self::TABLE));

        $this->table(self::STAGING, ['id' => false, 'primary_key' => ['product']])
            ->addColumn('product', 'string', ['limit' => 100, 'null' => false])
            ->addColumn('gen', 'biginteger', ['null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->create();

        foreach ($rows as $row) {
            $coordinate = (string) ($row['coordinate'] ?? '');

            if (!str_starts_with($coordinate, 'update:')) {
                continue;
            }

            $this->table(self::STAGING)->insert([
                'product' => substr($coordinate, strlen('update:')),
                'gen' => (int) ($row['gen'] ?? 0),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ])->saveData();
        }

        $this->table(self::TABLE)->drop()->save();
        $this->table(self::STAGING)->rename(self::TABLE)->save();
    }

    private function newTable(string $name): void
    {
        $this->table($name, ['id' => false, 'primary_key' => ['coordinate']])
            ->addColumn('coordinate', 'string', ['limit' => 255, 'null' => false])
            ->addColumn('gen', 'biginteger', ['null' => false])
            ->addColumn('updated_at', 'string', ['limit' => 32, 'null' => false])
            ->create();
    }
}

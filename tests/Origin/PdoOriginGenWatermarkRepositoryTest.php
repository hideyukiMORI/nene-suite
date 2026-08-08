<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Origin\OriginGenWatermarkCoordinate;
use NeNeSuite\Origin\PdoOriginGenWatermarkRepository;
use PHPUnit\Framework\TestCase;

final class PdoOriginGenWatermarkRepositoryTest extends TestCase
{
    private const string NOW = '2026-06-25T00:00:00Z';

    private PdoDatabaseQueryExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executor = new PdoDatabaseQueryExecutor(new PdoConnectionFactory(new DatabaseConfig(
            null,
            'test',
            'sqlite',
            'localhost',
            1,
            ':memory:',
            'nene-suite-test',
            '',
            'utf8',
        )));

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/origin_gen_watermarks.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testCurrentIsNullBeforeAnyRecord(): void
    {
        self::assertNull($this->repository()->current(self::update('nene-invoice')));
    }

    public function testRecordInsertsThenReads(): void
    {
        $repository = $this->repository();
        $repository->record(self::update('nene-invoice'), 42, self::NOW);

        self::assertSame(42, $repository->current(self::update('nene-invoice')));
    }

    public function testRecordAdvancesMonotonically(): void
    {
        $repository = $this->repository();
        $repository->record(self::update('nene-invoice'), 42, self::NOW);
        $repository->record(self::update('nene-invoice'), 50, self::NOW);

        self::assertSame(50, $repository->current(self::update('nene-invoice')));
    }

    public function testRecordNeverRegresses(): void
    {
        $repository = $this->repository();
        $repository->record(self::update('nene-invoice'), 50, self::NOW);
        $repository->record(self::update('nene-invoice'), 41, self::NOW); // lower generation must be ignored

        self::assertSame(50, $repository->current(self::update('nene-invoice')));
    }

    public function testRecordEqualGenerationIsNoOp(): void
    {
        $repository = $this->repository();
        $repository->record(self::update('nene-invoice'), 7, self::NOW);
        $repository->record(self::update('nene-invoice'), 7, self::NOW);

        self::assertSame(7, $repository->current(self::update('nene-invoice')));
    }

    public function testWatermarksAreProductScoped(): void
    {
        $repository = $this->repository();
        $repository->record(self::update('nene-invoice'), 5, self::NOW);
        $repository->record(self::update('nene-vault'), 9, self::NOW);

        self::assertSame(5, $repository->current(self::update('nene-invoice')));
        self::assertSame(9, $repository->current(self::update('nene-vault')));
    }

    /**
     * suite #424 at the storage layer: the three trees of one product are three rows, not one. With
     * the old `PRIMARY KEY (product)` this test could not even be written — every assertion below
     * would read whichever tree wrote last.
     */
    public function testTreesOfTheSameProductAreSeparateRows(): void
    {
        $repository = $this->repository();
        // The real corpus numbers for `nene-invoice`, which is what makes the collapse visible:
        // an update at 42 sitting on top of a feed at 7 rejects that feed as a rollback.
        $repository->record(self::update('nene-invoice'), 42, self::NOW);
        $repository->record(OriginGenWatermarkCoordinate::forFeed('nene-invoice', 'free', 'ja'), 7, self::NOW);
        $repository->record(OriginGenWatermarkCoordinate::forEntitlement('nene-invoice', 'paid'), 5, self::NOW);

        self::assertSame(42, $repository->current(self::update('nene-invoice')));
        self::assertSame(7, $repository->current(OriginGenWatermarkCoordinate::forFeed('nene-invoice', 'free', 'ja')));
        self::assertSame(5, $repository->current(OriginGenWatermarkCoordinate::forEntitlement('nene-invoice', 'paid')));
    }

    /**
     * The `ja` and `en` variants of one feed are independent counters (corpus: ja = 7, en = 3), so
     * the missing-locale fallback must not inherit the requested locale's floor.
     */
    public function testFeedLocalesAreSeparateRows(): void
    {
        $repository = $this->repository();
        $repository->record(OriginGenWatermarkCoordinate::forFeed('nene-invoice', 'free', 'ja'), 7, self::NOW);

        self::assertSame(7, $repository->current(OriginGenWatermarkCoordinate::forFeed('nene-invoice', 'free', 'ja')));
        self::assertNull($repository->current(OriginGenWatermarkCoordinate::forFeed('nene-invoice', 'free', 'en')));
    }

    private static function update(string $product): OriginGenWatermarkCoordinate
    {
        return OriginGenWatermarkCoordinate::forUpdate($product);
    }

    private function repository(): PdoOriginGenWatermarkRepository
    {
        return new PdoOriginGenWatermarkRepository($this->executor);
    }
}

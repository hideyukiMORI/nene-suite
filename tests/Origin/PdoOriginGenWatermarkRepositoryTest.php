<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
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
        self::assertNull($this->repository()->current('nene-invoice'));
    }

    public function testRecordInsertsThenReads(): void
    {
        $repository = $this->repository();
        $repository->record('nene-invoice', 42, self::NOW);

        self::assertSame(42, $repository->current('nene-invoice'));
    }

    public function testRecordAdvancesMonotonically(): void
    {
        $repository = $this->repository();
        $repository->record('nene-invoice', 42, self::NOW);
        $repository->record('nene-invoice', 50, self::NOW);

        self::assertSame(50, $repository->current('nene-invoice'));
    }

    public function testRecordNeverRegresses(): void
    {
        $repository = $this->repository();
        $repository->record('nene-invoice', 50, self::NOW);
        $repository->record('nene-invoice', 41, self::NOW); // lower generation must be ignored

        self::assertSame(50, $repository->current('nene-invoice'));
    }

    public function testRecordEqualGenerationIsNoOp(): void
    {
        $repository = $this->repository();
        $repository->record('nene-invoice', 7, self::NOW);
        $repository->record('nene-invoice', 7, self::NOW);

        self::assertSame(7, $repository->current('nene-invoice'));
    }

    public function testWatermarksAreProductScoped(): void
    {
        $repository = $this->repository();
        $repository->record('nene-invoice', 5, self::NOW);
        $repository->record('nene-vault', 9, self::NOW);

        self::assertSame(5, $repository->current('nene-invoice'));
        self::assertSame(9, $repository->current('nene-vault'));
    }

    private function repository(): PdoOriginGenWatermarkRepository
    {
        return new PdoOriginGenWatermarkRepository($this->executor);
    }
}

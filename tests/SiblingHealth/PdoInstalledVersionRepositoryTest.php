<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SiblingHealth;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\SiblingHealth\PdoInstalledVersionRepository;
use PHPUnit\Framework\TestCase;

final class PdoInstalledVersionRepositoryTest extends TestCase
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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/installed_app_versions.sql');
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
        $repository->record('nene-invoice', '1.3.0', self::NOW);

        self::assertSame('1.3.0', $repository->current('nene-invoice'));
    }

    public function testRecordOverwritesUpward(): void
    {
        $repository = $this->repository();
        $repository->record('nene-invoice', '1.3.0', self::NOW);
        $repository->record('nene-invoice', '1.4.0', self::NOW);

        self::assertSame('1.4.0', $repository->current('nene-invoice'));
    }

    public function testRecordOverwritesDownward(): void
    {
        // Last-write-wins (not monotonic): a reinstall / downgrade legitimately lowers the version.
        $repository = $this->repository();
        $repository->record('nene-invoice', '1.4.0', self::NOW);
        $repository->record('nene-invoice', '1.2.0', self::NOW);

        self::assertSame('1.2.0', $repository->current('nene-invoice'));
    }

    public function testVersionsAreProductScoped(): void
    {
        $repository = $this->repository();
        $repository->record('nene-invoice', '1.4.0', self::NOW);
        $repository->record('nene-vault', '2.0.0', self::NOW);

        self::assertSame('1.4.0', $repository->current('nene-invoice'));
        self::assertSame('2.0.0', $repository->current('nene-vault'));
    }

    private function repository(): PdoInstalledVersionRepository
    {
        return new PdoInstalledVersionRepository($this->executor);
    }
}

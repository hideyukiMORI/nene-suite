<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Auth\PdoLoginAttemptRepository;
use PHPUnit\Framework\TestCase;

final class PdoLoginAttemptRepositoryTest extends TestCase
{
    private const KEY = 'ip:203.0.113.7';

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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/login_attempts.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testRecordsAndCountsWithinWindow(): void
    {
        $repository = new PdoLoginAttemptRepository($this->executor);

        self::assertSame(0, $repository->countWithinWindow(self::KEY, 900, 1_000));
        self::assertSame(1, $repository->recordFailure(self::KEY, 900, 1_000));
        self::assertSame(2, $repository->recordFailure(self::KEY, 900, 1_100));
        self::assertSame(2, $repository->countWithinWindow(self::KEY, 900, 1_200));
    }

    public function testResetsWhenWindowExpires(): void
    {
        $repository = new PdoLoginAttemptRepository($this->executor);
        $repository->recordFailure(self::KEY, 900, 1_000);
        $repository->recordFailure(self::KEY, 900, 1_100);

        // 1_000 + 900 = 1_900; at 2_000 the window has expired → count resets to 0 / 1.
        self::assertSame(0, $repository->countWithinWindow(self::KEY, 900, 2_000));
        self::assertSame(1, $repository->recordFailure(self::KEY, 900, 2_000));
    }

    public function testClearRemovesTheKey(): void
    {
        $repository = new PdoLoginAttemptRepository($this->executor);
        $repository->recordFailure(self::KEY, 900, 1_000);
        $repository->clear(self::KEY);

        self::assertSame(0, $repository->countWithinWindow(self::KEY, 900, 1_000));
    }

    public function testDeleteExpiredReclaimsOnlyStaleRows(): void
    {
        $repository = new PdoLoginAttemptRepository($this->executor);
        $repository->recordFailure('ip:1.1.1.1', 900, 1_000);
        $repository->recordFailure('ip:2.2.2.2', 900, 5_000);

        // Reclaim rows whose window started before 5_000; the fresh 5_000 row survives.
        $repository->deleteExpired(5_000);

        self::assertSame(0, $repository->countWithinWindow('ip:1.1.1.1', 900, 5_000));
        self::assertSame(1, $repository->countWithinWindow('ip:2.2.2.2', 900, 5_000));
    }
}

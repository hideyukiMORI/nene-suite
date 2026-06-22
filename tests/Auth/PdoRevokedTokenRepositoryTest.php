<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Auth\PdoRevokedTokenRepository;
use PHPUnit\Framework\TestCase;

final class PdoRevokedTokenRepositoryTest extends TestCase
{
    private const JTI = '01J0SESSION00000000000000ZA';

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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/revoked_tokens.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testRevokeMakesTokenRevoked(): void
    {
        $repository = new PdoRevokedTokenRepository($this->executor);

        self::assertFalse($repository->isRevoked(self::JTI));
        $repository->revoke(self::JTI, 9_999_999_999, '2026-06-22T00:00:00Z', 'logout');
        self::assertTrue($repository->isRevoked(self::JTI));
    }

    public function testRevokeIsIdempotent(): void
    {
        $repository = new PdoRevokedTokenRepository($this->executor);
        $repository->revoke(self::JTI, 9_999_999_999, '2026-06-22T00:00:00Z', 'logout');
        $repository->revoke(self::JTI, 9_999_999_999, '2026-06-22T01:00:00Z', 'logout');

        self::assertTrue($repository->isRevoked(self::JTI));
    }

    public function testDeleteExpiredReclaimsOnlyExpiredRows(): void
    {
        $repository = new PdoRevokedTokenRepository($this->executor);
        $repository->revoke('01J0EXPIRED00000000000000ZA', 1_000, '2026-06-22T00:00:00Z', 'logout');
        $repository->revoke('01J0LIVE00000000000000000ZA', 9_999_999_999, '2026-06-22T00:00:00Z', 'logout');

        $repository->deleteExpired(2_000);

        self::assertFalse($repository->isRevoked('01J0EXPIRED00000000000000ZA'));
        self::assertTrue($repository->isRevoked('01J0LIVE00000000000000000ZA'));
    }
}

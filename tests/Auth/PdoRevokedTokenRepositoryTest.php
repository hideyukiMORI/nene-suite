<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Auth\PdoRevokedTokenRepository;
use NeNeSuite\Tests\Support\FixedClock;
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

    public function testGcBoundaryKeepsARowExpiringOnTheCurrentSecond(): void
    {
        // `expires_at < now` — a token expiring exactly now is still rejected on `exp` by the
        // verifier, but its revocation row must not be reclaimed a second early.
        $repository = new PdoRevokedTokenRepository($this->executor);
        $repository->revoke(self::JTI, 2_000, '2026-06-22T00:00:00Z', 'logout');

        $repository->deleteExpired(2_000);
        self::assertTrue($repository->isRevoked(self::JTI));

        $repository->deleteExpired(2_001);
        self::assertFalse($repository->isRevoked(self::JTI));
    }

    public function testOpportunisticGcReclaimsAgainstTheInjectedClockNotWallClock(): void
    {
        // The clock is pinned to 2001-09-09T01:46:40Z (epoch 1_000_000_000), far in the past. A row
        // expiring exactly on that instant must survive the piggybacked GC; a residual `time()`
        // call would compare against the real current second and wrongly reclaim it.
        $clock = new FixedClock('2001-09-09T01:46:40Z');
        $repository = new PdoRevokedTokenRepository($this->executor, $clock);

        $repository->revoke('01J0ATBOUNDARY0000000000ZA', $clock->timestamp(), '2026-06-22T00:00:00Z', 'logout');
        $repository->revoke('01J0PASTBOUNDARY00000000ZA', $clock->timestamp() - 1, '2026-06-22T00:00:00Z', 'logout');

        // GC piggybacks on ~1% of revocations. Over this many, the chance it never fires is ~2e-9;
        // the reclaimed-row assertion below fails loudly if it somehow did not.
        for ($i = 0; $i < 2_000; $i++) {
            $repository->revoke(sprintf('01J0FILLER%016d', $i), 9_999_999_999, '2026-06-22T00:00:00Z', 'logout');
        }

        self::assertFalse($repository->isRevoked('01J0PASTBOUNDARY00000000ZA'), 'GC did not run');
        self::assertTrue($repository->isRevoked('01J0ATBOUNDARY0000000000ZA'));
    }
}

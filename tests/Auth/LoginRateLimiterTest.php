<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\LoginRateLimitedException;
use NeNeSuite\Auth\LoginRateLimiter;
use NeNeSuite\Tests\Support\FixedClock;
use PHPUnit\Framework\TestCase;

final class LoginRateLimiterTest extends TestCase
{
    private const IP = '203.0.113.7';

    public function testAllowsAttemptsUnderTheCap(): void
    {
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(), 3, 900);
        $limiter->recordFailure(self::IP);
        $limiter->recordFailure(self::IP);

        $limiter->ensureWithinLimit(self::IP);

        $this->expectNotToPerformAssertions();
    }

    public function testThrowsWhenCapReached(): void
    {
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(), 3, 900);
        $limiter->recordFailure(self::IP);
        $limiter->recordFailure(self::IP);
        $limiter->recordFailure(self::IP);

        $this->expectException(LoginRateLimitedException::class);
        $limiter->ensureWithinLimit(self::IP);
    }

    public function testClearResetsTheWindow(): void
    {
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(), 3, 900);
        $limiter->recordFailure(self::IP);
        $limiter->recordFailure(self::IP);
        $limiter->recordFailure(self::IP);
        $limiter->clear(self::IP);

        $limiter->ensureWithinLimit(self::IP);

        $this->expectNotToPerformAssertions();
    }

    public function testEmptyClientIpIsNeverLimited(): void
    {
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(), 1, 900);
        $limiter->recordFailure('');

        $limiter->ensureWithinLimit('');

        $this->expectNotToPerformAssertions();
    }

    public function testFailsOpenWhenStoreThrows(): void
    {
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(throwOnUse: true), 1, 900);

        // A store error must not block logins (fail-open) nor surface as an exception.
        $limiter->recordFailure(self::IP);
        $limiter->ensureWithinLimit(self::IP);
        $limiter->clear(self::IP);

        $this->expectNotToPerformAssertions();
    }

    public function testRetryAfterIsTheWindowLength(): void
    {
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(), 1, 600);
        $limiter->recordFailure(self::IP);

        try {
            $limiter->ensureWithinLimit(self::IP);
            self::fail('expected LoginRateLimitedException');
        } catch (LoginRateLimitedException $exception) {
            self::assertSame(600, $exception->retryAfterSeconds);
        }
    }

    public function testWindowStaysClosedUntilItsFinalSecondHasElapsed(): void
    {
        // With a real clock the last second of the window is unreachable in a test; the injected
        // clock lets us stand exactly on it and assert the cap is still enforced.
        $clock = new FixedClock();
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(), 1, 600, $clock);
        $limiter->recordFailure(self::IP);

        $clock->advance(599);

        $this->expectException(LoginRateLimitedException::class);
        $limiter->ensureWithinLimit(self::IP);
    }

    public function testWindowReopensOnTheSecondItExpires(): void
    {
        $clock = new FixedClock();
        $limiter = new LoginRateLimiter(new InMemoryLoginAttemptRepository(), 1, 600, $clock);
        $limiter->recordFailure(self::IP);

        $clock->advance(600);
        $limiter->ensureWithinLimit(self::IP);

        $this->expectNotToPerformAssertions();
    }

    public function testWindowIsKeyedToTheInjectedInstantNotWallClock(): void
    {
        // The recorded window must come from the clock, not from `time()`. Pinning the clock far
        // from now proves the read is injected: a residual `time()` call would open the window at
        // the real current second and the stale-window branch would let the attempt through.
        $attempts = new InMemoryLoginAttemptRepository();
        $clock = new FixedClock('2001-09-09T01:46:40Z');
        $limiter = new LoginRateLimiter($attempts, 1, 600, $clock);

        $limiter->recordFailure(self::IP);

        self::assertSame(1, $attempts->countWithinWindow('ip:' . self::IP, 600, $clock->timestamp()));
    }
}

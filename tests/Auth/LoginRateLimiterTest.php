<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\LoginRateLimitedException;
use NeNeSuite\Auth\LoginRateLimiter;
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
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use Nene2\Http\ClockInterface;

/**
 * Test clock pinned to one instant, so time-dependent logic (`iat`/`exp`, rate-limit windows,
 * revoked-token GC) can be asserted exactly instead of approximately.
 *
 * `advance()` moves the instant forward to exercise boundaries — the moment a window rolls over,
 * or the second a token stops being expired — which a real clock cannot reach inside a test.
 */
final class FixedClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $instant = '2026-08-04T12:00:00Z')
    {
        $this->now = new DateTimeImmutable($instant, new DateTimeZone('UTC'));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function timestamp(): int
    {
        return $this->now->getTimestamp();
    }

    /** Moves the pinned instant forward (or back, with a negative value) by whole seconds. */
    public function advance(int $seconds): void
    {
        $this->now = $this->now->modify(sprintf('%+d seconds', $seconds));
    }
}

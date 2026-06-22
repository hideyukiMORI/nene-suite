<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * Fixed-window counter store for the login rate-limit (Phase B1.2). Keys are opaque (currently
 * only `ip:<addr>`). `$now` is epoch seconds, passed in so the window math is deterministic and
 * testable. This is a best-effort side counter, never inside a transaction: under concurrency the
 * fixed-window cap may be overshot by roughly the in-flight depth (acceptable for brute-force
 * slowing); credential verification is never bypassed.
 */
interface LoginAttemptRepositoryInterface
{
    /**
     * Current attempt count for the key within the window ending at `$now`. Returns 0 when there
     * is no row or the stored window has expired (a stale window does not count).
     */
    public function countWithinWindow(string $key, int $windowSeconds, int $now): int;

    /**
     * Records one failed attempt and returns the resulting in-window count. Starts a fresh window
     * (count = 1) when there is no row or the window has expired; otherwise increments.
     */
    public function recordFailure(string $key, int $windowSeconds, int $now): int;

    /** Clears the key (e.g. on a successful login). */
    public function clear(string $key): void;
}

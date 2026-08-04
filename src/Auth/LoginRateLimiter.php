<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\ClockInterface;
use Nene2\Http\UtcClock;
use Throwable;

/**
 * Per-client-IP login rate limit (Phase B1.2), persisted in the control DB so it survives
 * restarts and coordinates across workers/replicas. Enforces a hard per-IP cap within a fixed
 * window; on breach the caller gets 429.
 *
 * Per-email limiting is deliberately NOT enforced: a flat per-email cap is a griefing primitive
 * (an attacker locks a victim out by spamming their email), an account-existence oracle, and a
 * single-operator footgun (a fat-fingered operator locking themselves out of a single-operator
 * install). A non-lockout per-email velocity signal needs an escalation primitive (CAPTCHA /
 * step-up) that does not exist yet, so it is deferred (recorded in the B1.2 milestone row).
 *
 * Fail-OPEN on a store error — a DB hiccup must not lock out every login (ADR 0012 §10 degraded
 * availability) — but the open condition is logged because it is the one moment brute-force
 * protection is disabled. Fail-CLOSED on a real threshold breach.
 */
final readonly class LoginRateLimiter
{
    public const DEFAULT_MAX_ATTEMPTS = 10;

    public const DEFAULT_WINDOW_SECONDS = 900;

    public function __construct(
        private LoginAttemptRepositoryInterface $attempts,
        private int $maxAttempts = self::DEFAULT_MAX_ATTEMPTS,
        private int $windowSeconds = self::DEFAULT_WINDOW_SECONDS,
        // Defaulted so existing three-argument construction keeps working; the container passes
        // the shared clock so the whole runtime reads one instant source.
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    /**
     * @throws LoginRateLimitedException when the client IP has exceeded the cap in the window
     */
    public function ensureWithinLimit(string $clientIp): void
    {
        if ($clientIp === '') {
            // No resolvable client IP → cannot key a per-IP limit. Fail open rather than bucket
            // every request together (which would lock out all logins behind a misconfigured proxy).
            return;
        }

        try {
            $count = $this->attempts->countWithinWindow($this->key($clientIp), $this->windowSeconds, $this->now());
        } catch (Throwable $exception) {
            $this->failOpen('read', $exception);

            return;
        }

        if ($count >= $this->maxAttempts) {
            throw new LoginRateLimitedException($this->windowSeconds);
        }
    }

    public function recordFailure(string $clientIp): void
    {
        if ($clientIp === '') {
            return;
        }

        try {
            $this->attempts->recordFailure($this->key($clientIp), $this->windowSeconds, $this->now());
        } catch (Throwable $exception) {
            $this->failOpen('write', $exception);
        }
    }

    public function clear(string $clientIp): void
    {
        if ($clientIp === '') {
            return;
        }

        try {
            $this->attempts->clear($this->key($clientIp));
        } catch (Throwable $exception) {
            $this->failOpen('clear', $exception);
        }
    }

    private function key(string $clientIp): string
    {
        return 'ip:' . $clientIp;
    }

    /**
     * Read once per operation. Two reads inside one call can straddle a second boundary and
     * shift the window under the count that was just taken against it.
     */
    private function now(): int
    {
        return $this->clock->now()->getTimestamp();
    }

    private function failOpen(string $operation, Throwable $exception): void
    {
        // The single condition under which brute-force protection is disabled must not be silent.
        error_log(sprintf('[login-rate-limit] failing open on %s: %s', $operation, $exception->getMessage()));
    }
}

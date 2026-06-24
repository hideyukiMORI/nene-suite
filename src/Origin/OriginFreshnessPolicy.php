<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;

/**
 * Maps (`now`, `current.expires`) to an {@see OriginFreshnessState} (verification-order.md §4).
 * `expires` defaults to 30 days (set per tree in the object); `GRACE`/`HARD` below are the
 * client-side degradation windows applied *after* `expires`. They are the reference defaults — the
 * ordering (fresh → warn → refuse-new → hard) is the contract; the bounds match Origin's reference
 * verifier so Suite reproduces the corpus freshness cases.
 */
final class OriginFreshnessPolicy
{
    /** Warn window after `expires`: surface "update check stale" but keep accepting. */
    public const int GRACE_SECONDS = 604800;      // 7 days

    /** Beyond `expires + HARD_SECONDS` the pointer is treated as `hard` stale. */
    public const int HARD_SECONDS = 7776000;      // 90 days

    public static function evaluate(DateTimeImmutable $now, DateTimeImmutable $expires): OriginFreshnessState
    {
        if ($now < $expires) {
            return OriginFreshnessState::Fresh;
        }

        $staleSeconds = $now->getTimestamp() - $expires->getTimestamp();

        if ($staleSeconds < self::GRACE_SECONDS) {
            return OriginFreshnessState::Warn;
        }

        if ($staleSeconds < self::HARD_SECONDS) {
            return OriginFreshnessState::RefuseNew;
        }

        return OriginFreshnessState::Hard;
    }
}

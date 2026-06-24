<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The per-tree freshness state of a `current` pointer (verification-order.md §4). `gen` is the
 * primary anti-rollback signal; `expires` is only a freeze bound, so a stale pointer **degrades**
 * rather than bricking — a client keeps the version it has and merely refuses to accept *new*
 * content once it is too stale to trust.
 */
enum OriginFreshnessState: string
{
    case Fresh = 'fresh';
    case Warn = 'warn';
    case RefuseNew = 'refuse_new';
    case Hard = 'hard';

    /** Whether a client in this state may accept (apply) newly fetched content. */
    public function acceptsNew(): bool
    {
        return $this === self::Fresh || $this === self::Warn;
    }
}

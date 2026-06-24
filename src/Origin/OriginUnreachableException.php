<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use RuntimeException;

/**
 * The Origin read path could not be reached (network / transport failure).
 *
 * Origin is an **optional** signal source: a fetch failure degrades the dashboard
 * (no updates/announcements) but never blocks the suite. The HTTP-surface mapping
 * to Problem Details lands with the read API (O4); this is the transport-level signal.
 */
final class OriginUnreachableException extends RuntimeException
{
}

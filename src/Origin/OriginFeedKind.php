<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The two content-tree feed kinds (Origin ADR 0002 / 0006). Each is its own targets leaf under the
 * same `(product, audience, locale)` current, selected by the 4-part delegation key's last segment.
 */
enum OriginFeedKind: string
{
    case Announcement = 'announcement';
    case Ad = 'ad';
}

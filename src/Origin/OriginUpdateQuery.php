<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * One installed product to check against Origin: its catalog slug, the channel it tracks, and the
 * currently installed version. The aggregator's roster is a list of these (assembled from installed
 * apps + catalog by the read API in O4).
 */
final readonly class OriginUpdateQuery
{
    public function __construct(
        public string $product,
        public string $channel,
        public string $installedVersion,
    ) {
    }
}

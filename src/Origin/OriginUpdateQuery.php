<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * One installed product to check against Origin: its catalog slug, the channel it tracks, and the
 * currently installed version. The aggregator's roster is a list of these (assembled from installed
 * apps + catalog by the read API in O4). `installedVersion` is null when Suite does not yet track
 * the product's version — the manifest still surfaces (status `unknown`), no diff is claimed.
 */
final readonly class OriginUpdateQuery
{
    public function __construct(
        public string $product,
        public string $channel,
        public ?string $installedVersion,
    ) {
    }
}

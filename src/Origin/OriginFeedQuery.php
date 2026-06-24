<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * One content-tree feed to fetch: the product, the cohort `audience` (`free` | `paid`, from the
 * federation IdP claim — supplied by O4), the preferred `locale`, and the `kind`. Missing-locale
 * fallback to `en` is applied by {@see OriginFeedReader}, not encoded here.
 */
final readonly class OriginFeedQuery
{
    public function __construct(
        public string $product,
        public string $audience,
        public string $locale,
        public OriginFeedKind $kind,
    ) {
    }
}

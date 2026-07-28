<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use NeNeSuite\InstalledApps\ListInstalledAppsUseCaseInterface;

/**
 * Fans out the installed roster to fetch one content-tree feed kind (announcements or house-ads)
 * per product via the O3b {@see OriginFeedReader}. Disabled — {@see OriginFeedsOutput::disabled()} —
 * unless Origin is configured (a mirror list + an embedded trust anchor); no fabricated data.
 *
 * `audience` is `free` for now: the org tier (free/paid) is not tracked yet, and house-ads are a
 * `free`-only cohort (paid suppression lands when org-tier tracking does). `locale` is supplied by
 * the caller (validated en|ja in the handler); a missing locale falls back to `en` in the reader.
 */
final readonly class GetOriginFeedsUseCase
{
    private const string AUDIENCE = 'free';

    public function __construct(
        private OriginClientConfig $config,
        private OriginTrustAnchorProvider $anchors,
        private ListInstalledAppsUseCaseInterface $installed,
        private OriginObjectStoreProvider $stores,
        private OriginFeedReader $reader,
        private OriginGenWatermarkRepositoryInterface $watermarks,
    ) {
    }

    public function execute(OriginFeedKind $kind, string $locale, DateTimeImmutable $now): OriginFeedsOutput
    {
        $anchor = $this->anchors->load();

        if (!$this->config->isEnabled() || $anchor === null) {
            return OriginFeedsOutput::disabled();
        }

        $feeds = [];
        foreach ($this->installed->execute()->apps as $app) {
            $feeds[] = $this->reader->read(
                new OriginFeedQuery($app->catalogId, self::AUDIENCE, $locale, $kind),
                $this->stores,
                $anchor,
                $this->config->rootVersionFloor,
                $this->config->genFloor,
                $now,
                $this->watermarks,
            );
        }

        return OriginFeedsOutput::enabled($feeds);
    }
}

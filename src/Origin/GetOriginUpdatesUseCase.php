<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use NeNeSuite\InstalledApps\ListInstalledAppsUseCaseInterface;

/**
 * Assembles the roster of suite-installed products and checks each against Origin (O3 aggregator),
 * returning the verified update signals. Disabled — returns {@see OriginUpdatesOutput::disabled()} —
 * unless both `NENE_ORIGIN_URL` and an embedded trust anchor are configured (no fabricated data).
 * Installed versions are not tracked yet (O4 decision), so each query carries a null version and the
 * signal's status is `unknown` while still surfacing the verified latest.
 */
final readonly class GetOriginUpdatesUseCase
{
    private const string DEFAULT_CHANNEL = 'stable';

    public function __construct(
        private OriginClientConfig $config,
        private OriginTrustAnchorProvider $anchors,
        private ListInstalledAppsUseCaseInterface $installed,
        private OriginObjectStore $store,
        private OriginUpdateAggregator $aggregator,
        private OriginGenWatermarkRepositoryInterface $watermarks,
    ) {
    }

    public function execute(DateTimeImmutable $now): OriginUpdatesOutput
    {
        $anchor = $this->anchors->load();

        if (!$this->config->isEnabled() || $anchor === null) {
            return OriginUpdatesOutput::disabled();
        }

        $queries = [];
        foreach ($this->installed->execute()->apps as $app) {
            $queries[] = new OriginUpdateQuery($app->catalogId, self::DEFAULT_CHANNEL, null);
        }

        return OriginUpdatesOutput::enabled($this->aggregator->aggregate(
            $queries,
            $this->store,
            $anchor,
            $this->config->rootVersionFloor,
            $this->config->genFloor,
            $now,
            $this->watermarks,
        ));
    }
}

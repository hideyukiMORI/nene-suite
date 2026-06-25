<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use NeNeSuite\InstalledApps\ListInstalledAppsUseCaseInterface;
use NeNeSuite\SiblingHealth\InstalledVersionResolverInterface;

/**
 * Assembles the roster of suite-installed products and checks each against Origin (O3 aggregator),
 * returning the verified update signals. Disabled — returns {@see OriginUpdatesOutput::disabled()} —
 * unless both `NENE_ORIGIN_URL` and an embedded trust anchor are configured (no fabricated data).
 * Installed versions come from the sibling `/health` probe (#255); when a product's version is known
 * the signal's status is a real diff, otherwise it stays `unknown` while surfacing the verified
 * latest. Version resolution runs only on the enabled path (never probed when Origin is off).
 */
final readonly class GetOriginUpdatesUseCase
{
    private const string DEFAULT_CHANNEL = 'stable';

    public function __construct(
        private OriginClientConfig $config,
        private OriginTrustAnchorProvider $anchors,
        private ListInstalledAppsUseCaseInterface $installed,
        private InstalledVersionResolverInterface $versions,
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

        $apps = $this->installed->execute()->apps;
        $installedVersions = $this->versions->resolve($apps, $now);

        $queries = [];
        foreach ($apps as $app) {
            $queries[] = new OriginUpdateQuery(
                $app->catalogId,
                self::DEFAULT_CHANNEL,
                $installedVersions[$app->catalogId] ?? null,
            );
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

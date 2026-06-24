<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;

/**
 * Fans out over a roster of installed products and, for each, fetches + verifies its Origin update
 * tree (using the §2.4 consumer reduction — Suite is an aggregating client) and computes an
 * {@see OriginUpdateSignal}. The anti-rollback `persisted_gen` comes from the O2 watermark (falling
 * back to the build-time `gen` floor on first sight). A per-product verification failure or an
 * unreachable Origin degrades to an `unavailable` signal — it never aborts the whole roster.
 */
final readonly class OriginUpdateAggregator
{
    public function __construct(
        private OriginReadModelVerifier $verifier,
    ) {
    }

    /**
     * @param iterable<OriginUpdateQuery> $queries
     *
     * @return list<OriginUpdateSignal>
     */
    public function aggregate(
        iterable $queries,
        OriginObjectStore $store,
        OriginTrustAnchor $anchor,
        int $rootVersionFloor,
        int $genFloor,
        DateTimeImmutable $now,
        OriginGenWatermarkRepositoryInterface $watermarks,
    ): array {
        $signals = [];

        foreach ($queries as $query) {
            $signals[] = $this->evaluate($query, $store, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks);
        }

        return $signals;
    }

    private function evaluate(
        OriginUpdateQuery $query,
        OriginObjectStore $store,
        OriginTrustAnchor $anchor,
        int $rootVersionFloor,
        int $genFloor,
        DateTimeImmutable $now,
        OriginGenWatermarkRepositoryInterface $watermarks,
    ): OriginUpdateSignal {
        $persistedGen = $watermarks->current($query->product) ?? $genFloor;
        $client = new OriginClientState($persistedGen, $rootVersionFloor, $genFloor, $now);
        $request = new OriginVerificationRequest(
            OriginTreeKind::Update,
            sprintf('v1/%s/%s/current', $query->product, $query->channel),
            sprintf('%s/%s', $query->product, $query->channel),
            true, // consumer reduction (verification-order §2.4)
        );

        try {
            $outcome = $this->verifier->verify($store, $anchor, $client, $request);
        } catch (OriginUnreachableException) {
            return OriginUpdateSignal::unavailable($query, 'origin_unreachable');
        }

        if (!$outcome->accepted) {
            return OriginUpdateSignal::unavailable($query, $outcome->reason->value, $outcome->freshness);
        }

        $leaf = $outcome->leaf ?? [];
        $latest = is_array($leaf['latest'] ?? null) ? $leaf['latest'] : [];
        $latestVersion = is_string($latest['version'] ?? null) ? $latest['version'] : null;
        $minSupported = is_string($leaf['min_supported_version'] ?? null) ? $leaf['min_supported_version'] : null;
        $changelogUrl = is_string($latest['changelog_url'] ?? null) ? $latest['changelog_url'] : null;
        $releasedAt = is_string($latest['released_at'] ?? null) ? $latest['released_at'] : null;

        return new OriginUpdateSignal(
            product: $query->product,
            channel: $query->channel,
            installedVersion: $query->installedVersion,
            status: $this->status($query->installedVersion, $latestVersion, $minSupported),
            latestVersion: $latestVersion,
            minSupportedVersion: $minSupported,
            changelogUrl: $changelogUrl,
            releasedAt: $releasedAt,
            freshness: $outcome->freshness,
            warnings: $outcome->warnings,
        );
    }

    private function status(?string $installed, ?string $latest, ?string $minSupported): OriginUpdateStatus
    {
        // No installed-version tracking yet (O4 decision): the manifest is verified but no diff can
        // be claimed — surface the latest, status unknown.
        if ($installed === null) {
            return OriginUpdateStatus::Unknown;
        }

        if ($minSupported !== null && version_compare($installed, $minSupported, '<')) {
            return OriginUpdateStatus::Forced;
        }

        if ($latest !== null && version_compare($installed, $latest, '<')) {
            return OriginUpdateStatus::UpdateAvailable;
        }

        return OriginUpdateStatus::UpToDate;
    }
}

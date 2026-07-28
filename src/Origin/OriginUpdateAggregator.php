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
 *
 * **Mirror failover** (mirrors.md §4): one product = one walk, and each walk is retried in mirror
 * order until one *fully verifies*. A transport failure and a verification REJECT are treated
 * identically — a broken or hostile mirror is a denial — and each skipped mirror is surfaced as a
 * warning on the resulting signal. Objects are never mixed across bases within a walk (§4.1).
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
        OriginObjectStoreProvider $stores,
        OriginTrustAnchor $anchor,
        int $rootVersionFloor,
        int $genFloor,
        DateTimeImmutable $now,
        OriginGenWatermarkRepositoryInterface $watermarks,
    ): array {
        $signals = [];

        foreach ($queries as $query) {
            $signals[] = $this->evaluate($query, $stores, $anchor, $rootVersionFloor, $genFloor, $now, $watermarks);
        }

        return $signals;
    }

    private function evaluate(
        OriginUpdateQuery $query,
        OriginObjectStoreProvider $stores,
        OriginTrustAnchor $anchor,
        int $rootVersionFloor,
        int $genFloor,
        DateTimeImmutable $now,
        OriginGenWatermarkRepositoryInterface $watermarks,
    ): OriginUpdateSignal {
        $persistedGen = $watermarks->current($query->product) ?? $genFloor;
        $request = new OriginVerificationRequest(
            OriginTreeKind::Update,
            sprintf('v1/%s/%s/current', $query->product, $query->channel),
            sprintf('%s/%s', $query->product, $query->channel),
            true, // consumer reduction (verification-order §2.4)
        );

        $mirrorWarnings = [];
        $attempted = false;
        $lastReason = 'origin_unreachable';
        $lastFreshness = null;
        $outcome = null;

        foreach ($stores->stores() as $baseUrl => $store) {
            $attempted = true;
            // A fresh client state per attempt: each mirror is walked from the embedded root.
            $client = new OriginClientState($persistedGen, $rootVersionFloor, $genFloor, $now);

            try {
                $attempt = $this->verifier->verify($store, $anchor, $client, $request);
            } catch (OriginUnreachableException) {
                $mirrorWarnings[] = self::mirrorWarning($baseUrl, 'origin_unreachable');
                $lastReason = 'origin_unreachable';
                $lastFreshness = null;

                continue;
            }

            if (!$attempt->accepted) {
                // A verification REJECT is a denial by this mirror, not a verdict on the tree.
                $mirrorWarnings[] = self::mirrorWarning($baseUrl, $attempt->reason->value);
                $lastReason = $attempt->reason->value;
                $lastFreshness = $attempt->freshness;

                continue;
            }

            $outcome = $attempt;

            break;
        }

        if ($outcome === null) {
            return OriginUpdateSignal::unavailable(
                $query,
                $attempted ? $lastReason : 'origin_not_configured',
                $lastFreshness,
                $mirrorWarnings,
            );
        }

        $leaf = $outcome->leaf ?? [];
        $latest = is_array($leaf['latest'] ?? null) ? $leaf['latest'] : [];
        $latestVersion = is_string($latest['version'] ?? null) ? $latest['version'] : null;
        $minSupported = is_string($leaf['min_supported_version'] ?? null) ? $leaf['min_supported_version'] : null;
        $changelogUrl = is_string($latest['changelog_url'] ?? null) ? $latest['changelog_url'] : null;
        $releasedAt = is_string($latest['released_at'] ?? null) ? $latest['released_at'] : null;

        $requires = [];
        $rawRequires = is_array($latest['requires'] ?? null) ? $latest['requires'] : [];
        foreach ($rawRequires as $requiredProduct => $constraint) {
            if (is_string($requiredProduct) && is_string($constraint) && $constraint !== '') {
                $requires[$requiredProduct] = $constraint;
            }
        }

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
            warnings: [...$mirrorWarnings, ...$outcome->warnings],
            requires: $requires,
        );
    }

    /** The per-mirror surface required by mirrors.md §4.2 — local only, never transmitted (§4.5). */
    private static function mirrorWarning(string $baseUrl, string $reason): string
    {
        return sprintf('mirror failover: %s skipped (%s)', $baseUrl, $reason);
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

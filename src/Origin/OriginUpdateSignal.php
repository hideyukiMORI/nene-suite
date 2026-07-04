<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The verified update standing for one product — the unit the dashboard renders. On a resolved
 * signal the version/changelog fields come from the **verified** manifest (never trusted before the
 * chain verified). On `unavailable`, `reason` carries the stable verification reason (or
 * `origin_unreachable`) so the UI can degrade transparently rather than inventing data.
 */
final readonly class OriginUpdateSignal
{
    /**
     * @param list<string>          $warnings
     * @param array<string, string> $requires min-compatible sibling versions from the verified
     *                                        manifest (`latest.requires`, e.g. `nene-invoice => >=1.3.0`) —
     *                                        consumed by the deploy planner (ADR 0019 §3), not the wire view
     */
    public function __construct(
        public string $product,
        public string $channel,
        public ?string $installedVersion,
        public OriginUpdateStatus $status,
        public ?string $latestVersion = null,
        public ?string $minSupportedVersion = null,
        public ?string $changelogUrl = null,
        public ?string $releasedAt = null,
        public ?OriginFreshnessState $freshness = null,
        public ?string $reason = null,
        public array $warnings = [],
        public array $requires = [],
    ) {
    }

    public static function unavailable(OriginUpdateQuery $query, string $reason, ?OriginFreshnessState $freshness = null): self
    {
        return new self(
            product: $query->product,
            channel: $query->channel,
            installedVersion: $query->installedVersion,
            status: OriginUpdateStatus::Unavailable,
            freshness: $freshness,
            reason: $reason,
        );
    }
}

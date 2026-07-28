<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Resolved Origin client settings. `mirrors` is the ordered read-path base list — the embedded
 * default when `NENE_ORIGIN_URL` is unset, or exactly that one base when it is set (mirrors.md §3);
 * an empty list = Origin disabled. `rootVersionFloor` / `genFloor` are the build-time anti-rollback
 * floors (a withheld root rotation / replayed generation fails closed). `trustAnchorPath` points at
 * the embedded root trust (`trust-anchor.json`); null = no anchor = Origin disabled (production keys
 * are a human-gated ceremony, so dev/unconfigured installs stay off — the embedded mirror list on
 * its own never turns the client on).
 */
final readonly class OriginClientConfig
{
    public function __construct(
        public OriginMirrorList $mirrors,
        public int $timeoutSeconds,
        public int $rootVersionFloor,
        public int $genFloor,
        public ?string $trustAnchorPath,
    ) {
    }

    public function isEnabled(): bool
    {
        return !$this->mirrors->isEmpty() && $this->trustAnchorPath !== null && $this->trustAnchorPath !== '';
    }
}

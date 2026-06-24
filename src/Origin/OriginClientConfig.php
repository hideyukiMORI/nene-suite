<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Resolved Origin client settings. `baseUrl` is `NENE_ORIGIN_URL` (empty when unset = Origin
 * disabled; the dashboard simply shows no Origin signals). `rootVersionFloor` / `genFloor` are the
 * build-time anti-rollback floors (a withheld root rotation / replayed generation fails closed).
 * `trustAnchorPath` points at the embedded root trust (`trust-anchor.json`); null = no anchor =
 * Origin disabled (production keys are a human-gated ceremony, so dev/unconfigured installs stay off).
 */
final readonly class OriginClientConfig
{
    public function __construct(
        public string $baseUrl,
        public int $timeoutSeconds,
        public int $rootVersionFloor,
        public int $genFloor,
        public ?string $trustAnchorPath,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->baseUrl !== '' && $this->trustAnchorPath !== null && $this->trustAnchorPath !== '';
    }
}

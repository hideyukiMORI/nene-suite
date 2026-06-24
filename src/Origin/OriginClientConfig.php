<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Resolved Origin client settings. `baseUrl` is `NENE_ORIGIN_URL` (empty when
 * unset = Origin disabled; the dashboard simply shows no Origin signals). Root
 * public-key pinning and the build-time `gen`/date floor are added with the
 * verifier (O1); this object carries the transport-level configuration.
 */
final readonly class OriginClientConfig
{
    public function __construct(
        public string $baseUrl,
        public int $timeoutSeconds,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->baseUrl !== '';
    }
}

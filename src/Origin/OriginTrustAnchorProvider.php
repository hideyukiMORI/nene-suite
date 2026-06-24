<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use JsonException;

/**
 * Loads the embedded root trust ({@see OriginTrustAnchor}) a client ships at build time
 * (verification-order.md §2.1 — no TOFU). The path is configured (`NENE_ORIGIN_TRUST_ANCHOR_PATH`);
 * an unset/unreadable/malformed anchor yields null, which keeps the Origin client disabled rather
 * than trusting runtime-discovered keys. Production anchors are a human-gated key ceremony.
 */
final readonly class OriginTrustAnchorProvider
{
    public function __construct(
        private ?string $path,
    ) {
    }

    public function load(): ?OriginTrustAnchor
    {
        if ($this->path === null || $this->path === '') {
            return null;
        }

        $bytes = @file_get_contents($this->path);
        if ($bytes === false) {
            return null;
        }

        try {
            $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        return OriginTrustAnchor::fromArray($decoded);
    }
}

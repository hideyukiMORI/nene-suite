<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The root trust a client ships in its build (no TOFU, verification-order.md §2.1): the embedded
 * root public keys (current + next) plus the `root_threshold`. `root.json` is verified against THIS,
 * never against keys discovered at runtime. Origin publishes the embeddable bytes as
 * `trust-anchor.json`.
 */
final readonly class OriginTrustAnchor
{
    /**
     * @param list<string>                           $rootKeyids the kids authorised to sign root.json
     * @param array<string, OriginPublicKeyMaterial> $keys       embedded root key material, by kid
     */
    public function __construct(
        public array $rootKeyids,
        public int $rootThreshold,
        public array $keys,
    ) {
    }

    /**
     * Build from decoded `trust-anchor.json` (`{root_keyids, root_threshold, keys[]}`).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $keys = [];
        $rawKeys = $data['keys'] ?? [];

        if (is_array($rawKeys)) {
            foreach ($rawKeys as $jwk) {
                if (is_array($jwk)) {
                    $material = OriginPublicKeyMaterial::tryFromJwk($jwk);
                    if ($material !== null) {
                        $keys[$material->kid] = $material;
                    }
                }
            }
        }

        $rawKeyids = $data['root_keyids'] ?? [];
        /** @var list<string> $rootKeyids */
        $rootKeyids = is_array($rawKeyids) ? array_values(array_filter($rawKeyids, 'is_string')) : [];

        return new self($rootKeyids, (int) ($data['root_threshold'] ?? 0), $keys);
    }
}

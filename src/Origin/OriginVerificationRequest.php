<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * What to verify: which tree, the `current` entry path, the snapshot delegation key for this
 * product/coordinate, and whether to apply the §2.4 single-target **consumer reduction** (use
 * `current.targets_sha256` directly and skip the snapshot fetch). The authority always publishes the
 * snapshot; a per-product consumer (including the aggregating Suite client) MAY reduce to keep
 * fetch O(N).
 */
final readonly class OriginVerificationRequest
{
    public function __construct(
        public OriginTreeKind $tree,
        public string $entry,
        public string $delegationKey,
        public bool $reduce,
    ) {
    }

    /**
     * @param array<string, mixed> $data decoded `case.json`
     */
    public static function fromArray(array $data): self
    {
        return new self(
            OriginTreeKind::from(is_string($data['tree'] ?? null) ? (string) $data['tree'] : ''),
            is_string($data['entry'] ?? null) ? (string) $data['entry'] : '',
            is_string($data['delegation_key'] ?? null) ? (string) $data['delegation_key'] : '',
            (bool) ($data['reduce'] ?? false),
        );
    }
}

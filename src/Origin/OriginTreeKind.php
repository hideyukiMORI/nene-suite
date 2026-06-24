<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Which signed tree a verification walks (Origin ADR 0006 §2). All three share the shape
 * `current → [snapshot] → targets-leaf`, but differ in the leaf's content-addressed directory, the
 * delegated targets role that signs the leaf, and what the leaf points at downstream:
 *
 * - **update**       — `targets` (= version manifest), targets role; leaf covers the artifact sha256.
 * - **feed**         — `feed-targets`, feed role; leaf points at the feed body via `content_sha256`.
 * - **entitlement**  — entitlement policy, entitlement role; the leaf is terminal.
 */
enum OriginTreeKind: string
{
    case Update = 'update';
    case Feed = 'feed';
    case Entitlement = 'entitlement';

    /** Content-addressed subdirectory under `/v1` holding this tree's targets leaf. */
    public function targetsDirectory(): string
    {
        return match ($this) {
            self::Update => 'targets',
            self::Feed => 'feed-targets',
            self::Entitlement => 'entitlement-policies',
        };
    }

    /** The `root.json` role name that signs this tree's targets leaf. */
    public function targetsRole(): string
    {
        return match ($this) {
            self::Update => 'targets',
            self::Feed => 'feed',
            self::Entitlement => 'entitlement',
        };
    }
}

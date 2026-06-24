<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use Exception;
use JsonException;

/**
 * The Suite implementation of the profiled-TUF client MUST algorithm (verification-order.md), a
 * faithful port of Origin's canonical reference verifier (ADR 0006 §8). It walks one tree from the
 * embedded root down to the artifact / feed body, trusting no field until its object's signature
 * chain has verified, and returns a single {@see OriginVerificationOutcome}. Parity with the signed
 * conformance corpus is the cross-repo merge gate. Verification is offline and uses Ed25519 + SHA-256.
 */
final class OriginReadModelVerifier
{
    public const int SUPPORTED_SPEC_VERSION = 1;

    public const int SUPPORTED_SCHEMA_VERSION = 1;

    public function verify(
        OriginObjectStore $store,
        OriginTrustAnchor $anchor,
        OriginClientState $client,
        OriginVerificationRequest $request,
    ): OriginVerificationOutcome {
        // 1. Root trust (no TOFU): verify root.json against the EMBEDDED root keys + threshold.
        $rootBytes = $store->read('v1/root.json');
        $rootCheck = OriginDetachedJwsVerifier::verify(
            $store->readSidecar('v1/root.json'),
            $rootBytes,
            $anchor->keys,
            $anchor->rootKeyids,
            $anchor->rootThreshold,
        );
        if (!$rootCheck->ok) {
            $reason = $rootCheck->reason === OriginVerificationReason::RoleThresholdNotMet
                ? OriginVerificationReason::RootSignatureBelowThreshold
                : ($rootCheck->reason ?? OriginVerificationReason::RootSignatureBelowThreshold);

            return OriginVerificationOutcome::reject($reason, 'root');
        }

        $root = $this->decodeJson($rootBytes);
        if ($root === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::MalformedObject, 'root');
        }
        if (($root['spec_version'] ?? null) !== self::SUPPORTED_SPEC_VERSION) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnsupportedSpecVersion, 'root');
        }
        // root.expires is intentionally not evaluated here — freshness is tied to each tree's
        // `current` (§4); root is long-lived. Root-version floor: a withheld rotation fails closed.
        if ((int) ($root['version'] ?? 0) < $client->rootVersionFloor) {
            return OriginVerificationOutcome::reject(
                OriginVerificationReason::RootVersionBelowFloor,
                'root',
                'root.json version is below the client root-version floor',
            );
        }

        // 2. Resolve role delegations + their published key material.
        $keys = $this->loadKeys($root['keys'] ?? null);
        $timestamp = $this->role($root, 'timestamp');
        if ($timestamp === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnknownRole, 'current');
        }

        // 3. Verify `current` (timestamp role), then enforce its state machine.
        $currentBytes = $store->read($request->entry);
        $currentCheck = OriginDetachedJwsVerifier::verify(
            $store->readSidecar($request->entry),
            $currentBytes,
            $keys,
            $timestamp['keyids'],
            $timestamp['threshold'],
        );
        if (!$currentCheck->ok) {
            return OriginVerificationOutcome::reject($currentCheck->reason ?? OriginVerificationReason::SignatureInvalid, 'current');
        }
        $current = $this->decodeJson($currentBytes);
        if ($current === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::MalformedObject, 'current');
        }
        if (($current['spec_version'] ?? null) !== self::SUPPORTED_SPEC_VERSION) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnsupportedSpecVersion, 'current');
        }
        if ((int) ($current['schema_version'] ?? 0) > self::SUPPORTED_SCHEMA_VERSION) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnsupportedSchemaVersion, 'current');
        }

        $gen = (int) ($current['gen'] ?? 0);
        // Anti-rollback: gen MUST be >= the persisted last-applied and >= the build-time floor.
        if ($gen < $client->persistedGen || $gen < $client->genFloor) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::Rollback, 'current', "current.gen {$gen} is below the persisted/build floor");
        }
        // Yank watermark: refuse a generation at or below min_valid_generation, even if cached.
        $minValidGeneration = isset($current['min_valid_generation']) ? (int) $current['min_valid_generation'] : null;
        if ($minValidGeneration !== null && $gen < $minValidGeneration) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::Yanked, 'current', "current.gen {$gen} is below min_valid_generation {$minValidGeneration}");
        }
        // Freshness: degrade rather than brick; refuse-new / hard means do not accept new content.
        $expires = $this->parseTimestamp($current['expires'] ?? null);
        if ($expires === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::MalformedObject, 'current', 'current.expires is missing or unparseable');
        }
        $freshness = OriginFreshnessPolicy::evaluate($client->now, $expires);
        if (!$freshness->acceptsNew()) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::FreshnessRefuseNew, 'current', "current is stale: {$freshness->value}", $freshness);
        }
        $warnings = $freshness === OriginFreshnessState::Warn ? ['freshness warn: update check stale'] : [];

        // 4. Reach `targets`: full snapshot path, or the §2.4 single-target consumer reduction.
        $targetsSha = $this->reachTargets($store, $root, $keys, $current, $gen, $request);
        if ($targetsSha instanceof OriginVerificationOutcome) {
            return $targetsSha; // a failure along the snapshot hop
        }

        // 5. Verify the `targets` leaf with its delegated role (targets / feed / entitlement).
        $tree = $request->tree;
        $targetsBytes = $store->readByHash($tree->targetsDirectory(), $targetsSha);
        if ($this->sha256Hex($targetsBytes) !== $targetsSha) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::TargetsHashMismatch, 'targets');
        }
        $targetsRole = $this->role($root, $tree->targetsRole());
        if ($targetsRole === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnknownRole, 'targets');
        }
        $targetsCheck = OriginDetachedJwsVerifier::verify(
            $store->readSidecarByHash($tree->targetsDirectory(), $targetsSha),
            $targetsBytes,
            $keys,
            $targetsRole['keyids'],
            $targetsRole['threshold'],
        );
        if (!$targetsCheck->ok) {
            return OriginVerificationOutcome::reject($targetsCheck->reason ?? OriginVerificationReason::SignatureInvalid, 'targets');
        }
        $targets = $this->decodeJson($targetsBytes);
        if ($targets === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::MalformedObject, 'targets');
        }
        if ((int) ($targets['schema_version'] ?? 0) > self::SUPPORTED_SCHEMA_VERSION) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnsupportedSchemaVersion, 'targets');
        }
        // Bind the signed leaf to the REQUESTED coordinates: one authority signs every product with
        // the same role keys, so a validly-signed leaf for coordinate X must not be accepted when the
        // client asked for Y (cross-coordinate mix-and-match).
        if (!$this->coordinatesBind($tree, $request->delegationKey, $targets)) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::CoordinateMismatch, 'targets', 'the signed leaf does not match the requested coordinates');
        }
        // Secondary per-leaf yank for a tree whose leaf carries its own `gen` (e.g. feed); the primary
        // generation yank is enforced on `current` above.
        if ($minValidGeneration !== null && isset($targets['gen']) && (int) $targets['gen'] < $minValidGeneration) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::Yanked, 'targets');
        }

        // 6/7. Trust fields, then verify the artifact / feed body hash before "apply".
        return match ($tree) {
            OriginTreeKind::Update => $this->verifyArtifact($store, $targets, $freshness, $warnings),
            OriginTreeKind::Feed => $this->verifyFeedBody($store, $targets, $freshness, $warnings),
            OriginTreeKind::Entitlement => $this->verifyEntitlementPolicy($targets, $freshness, $warnings),
        };
    }

    /**
     * @param array<string, mixed>                    $root
     * @param array<string, OriginPublicKeyMaterial>  $keys
     * @param array<string, mixed>                    $current
     *
     * @return string|OriginVerificationOutcome the resolved targets sha256, or a rejection
     */
    private function reachTargets(OriginObjectStore $store, array $root, array $keys, array $current, int $gen, OriginVerificationRequest $request): string|OriginVerificationOutcome
    {
        // Consumer reduction (verification-order §2.4): trust current.targets_sha256, skip snapshot.
        if ($request->reduce && isset($current['targets_sha256']) && is_string($current['targets_sha256'])) {
            return $current['targets_sha256'];
        }

        $snapshotSha = is_string($current['snapshot_sha256'] ?? null) ? (string) $current['snapshot_sha256'] : '';
        $snapshotBytes = $store->readByHash('snapshot', $snapshotSha);
        if ($this->sha256Hex($snapshotBytes) !== $snapshotSha) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::SnapshotHashMismatch, 'snapshot');
        }
        $snapshotRole = $this->role($root, 'snapshot');
        if ($snapshotRole === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnknownRole, 'snapshot');
        }
        $snapshotCheck = OriginDetachedJwsVerifier::verify(
            $store->readSidecarByHash('snapshot', $snapshotSha),
            $snapshotBytes,
            $keys,
            $snapshotRole['keyids'],
            $snapshotRole['threshold'],
        );
        if (!$snapshotCheck->ok) {
            return OriginVerificationOutcome::reject($snapshotCheck->reason ?? OriginVerificationReason::SignatureInvalid, 'snapshot');
        }
        $snapshot = $this->decodeJson($snapshotBytes);
        if ($snapshot === null) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::MalformedObject, 'snapshot');
        }
        if ((int) ($snapshot['schema_version'] ?? 0) > self::SUPPORTED_SCHEMA_VERSION) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::UnsupportedSchemaVersion, 'snapshot');
        }
        if ((int) ($snapshot['gen'] ?? -1) !== $gen) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::SnapshotGenMismatch, 'snapshot');
        }
        $targetsMap = $snapshot['targets'] ?? null;
        $targetsSha = is_array($targetsMap) ? ($targetsMap[$request->delegationKey] ?? null) : null;
        if (!is_string($targetsSha)) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::TargetsHashMismatch, 'snapshot', 'snapshot does not delegate the requested target');
        }

        return $targetsSha;
    }

    /**
     * @param array<string, mixed> $targets
     * @param list<string>         $warnings
     */
    private function verifyArtifact(OriginObjectStore $store, array $targets, OriginFreshnessState $freshness, array $warnings): OriginVerificationOutcome
    {
        $latest = $targets['latest'] ?? null;
        $artifactSha = is_array($latest) ? ($latest['artifact_sha256'] ?? null) : null;
        if (!is_string($artifactSha)) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::ArtifactHashMismatch, 'artifact', 'targets does not cover an artifact_sha256');
        }
        if ($this->sha256Hex($store->readArtifact($artifactSha)) !== $artifactSha) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::ArtifactHashMismatch, 'artifact');
        }

        return OriginVerificationOutcome::accept('complete', $freshness, $warnings);
    }

    /**
     * @param array<string, mixed> $feed
     * @param list<string>         $warnings
     */
    private function verifyFeedBody(OriginObjectStore $store, array $feed, OriginFreshnessState $freshness, array $warnings): OriginVerificationOutcome
    {
        $contentSha = $feed['content_sha256'] ?? null;
        if (!is_string($contentSha)) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::ContentHashMismatch, 'feed-body', 'feed does not carry content_sha256');
        }
        if ($this->sha256Hex($store->readByHash('feed-bodies', $contentSha)) !== $contentSha) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::ContentHashMismatch, 'feed-body');
        }

        return OriginVerificationOutcome::accept('complete', $freshness, $warnings);
    }

    /**
     * The entitlement object is an audience policy + a `min_valid_generation` watermark; the
     * token-vs-watermark comparison is a consumer concern outside this verifier (Origin ADR 0006 §7).
     * Here we confirm the signed policy reached the client intact and carries a well-formed watermark.
     *
     * @param array<string, mixed> $policy
     * @param list<string>         $warnings
     */
    private function verifyEntitlementPolicy(array $policy, OriginFreshnessState $freshness, array $warnings): OriginVerificationOutcome
    {
        $watermark = $policy['min_valid_generation'] ?? null;
        if (!is_int($watermark) || $watermark < 1) {
            return OriginVerificationOutcome::reject(OriginVerificationReason::MalformedObject, 'entitlement', 'entitlement policy is missing a valid min_valid_generation watermark');
        }

        return OriginVerificationOutcome::accept('complete', $freshness, $warnings);
    }

    /**
     * Whether the signed leaf's self-declared coordinates match the requested delegation key
     * (`product/channel` for update, `product/audience/locale/kind` for feed, `product/audience` for
     * entitlement) — the binding that stops a validly-signed leaf for one coordinate being served at
     * another's path.
     *
     * @param array<string, mixed> $leaf
     */
    private function coordinatesBind(OriginTreeKind $tree, string $delegationKey, array $leaf): bool
    {
        $parts = explode('/', $delegationKey);

        return match ($tree) {
            OriginTreeKind::Update => count($parts) === 2
                && ($leaf['product'] ?? null) === $parts[0]
                && ($leaf['channel'] ?? null) === $parts[1],
            OriginTreeKind::Feed => count($parts) === 4
                && ($leaf['product'] ?? null) === $parts[0]
                && ($leaf['audience'] ?? null) === $parts[1]
                && ($leaf['locale'] ?? null) === $parts[2]
                && ($leaf['kind'] ?? null) === $parts[3],
            OriginTreeKind::Entitlement => count($parts) === 2
                && ($leaf['product'] ?? null) === $parts[0]
                && ($leaf['audience'] ?? null) === $parts[1],
        };
    }

    private function parseTimestamp(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @return array<string, OriginPublicKeyMaterial>
     */
    private function loadKeys(mixed $jwks): array
    {
        $keys = [];
        if (is_array($jwks)) {
            foreach ($jwks as $jwk) {
                if (is_array($jwk)) {
                    $material = OriginPublicKeyMaterial::tryFromJwk($jwk);
                    if ($material !== null) {
                        $keys[$material->kid] = $material;
                    }
                }
            }
        }

        return $keys;
    }

    /**
     * @param array<string, mixed> $root
     *
     * @return array{keyids: list<string>, threshold: int}|null
     */
    private function role(array $root, string $name): ?array
    {
        $roles = $root['roles'] ?? null;
        if (!is_array($roles)) {
            return null;
        }
        $role = $roles[$name] ?? null;
        if (!is_array($role) || !isset($role['keyids'], $role['threshold']) || !is_array($role['keyids'])) {
            return null;
        }

        /** @var list<string> $keyids */
        $keyids = array_values(array_filter($role['keyids'], 'is_string'));

        return ['keyids' => $keyids, 'threshold' => (int) $role['threshold']];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $bytes): ?array
    {
        try {
            $decoded = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function sha256Hex(string $bytes): string
    {
        return hash('sha256', $bytes);
    }
}

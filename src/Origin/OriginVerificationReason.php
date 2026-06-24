<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The single stable reason a profiled-TUF verification accepted or rejected, mirroring the Origin
 * reference verifier's vocabulary (verification-order.md / conformance corpus). The string values
 * are the cross-repo contract — the corpus asserts on them, so they must not drift.
 */
enum OriginVerificationReason: string
{
    case Ok = 'ok';

    // Signature envelope / key policy.
    case AlgNotAllowed = 'alg_not_allowed';
    case KidUnknown = 'kid_unknown';
    case KidNotValidAtIat = 'kid_not_valid_at_iat';
    case SignatureInvalid = 'signature_invalid';
    case RoleThresholdNotMet = 'role_threshold_not_met';
    case MalformedObject = 'malformed_object';

    // Root trust.
    case RootSignatureBelowThreshold = 'root_signature_below_threshold';
    case RootVersionBelowFloor = 'root_version_below_floor';
    case UnknownRole = 'unknown_role';

    // Coordinate binding.
    case CoordinateMismatch = 'coordinate_mismatch';

    // Generation / freshness.
    case Rollback = 'rollback';
    case Yanked = 'yanked';
    case FreshnessRefuseNew = 'freshness_refuse_new';

    // Content addressing.
    case SnapshotHashMismatch = 'snapshot_hash_mismatch';
    case SnapshotGenMismatch = 'snapshot_gen_mismatch';
    case TargetsHashMismatch = 'targets_hash_mismatch';
    case ArtifactHashMismatch = 'artifact_hash_mismatch';
    case ContentHashMismatch = 'content_hash_mismatch';

    // Versioning.
    case UnsupportedSpecVersion = 'unsupported_spec_version';
    case UnsupportedSchemaVersion = 'unsupported_schema_version';
}

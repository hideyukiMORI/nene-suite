<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * A condition that makes the plan non-executable (ADR 0019 §3 min-version gating and
 * the structural guards around it). Conflicts are surfaced, never silently dropped.
 */
final readonly class DeployPlanConflict
{
    public const TYPE_MISSING_DIGEST_PIN = 'missing_digest_pin';

    public const TYPE_MISSING_DEPENDENCY = 'missing_dependency';

    public const TYPE_DEPENDENCY_CYCLE = 'dependency_cycle';

    public const TYPE_UNKNOWN_DEPENDENCY_VERSION = 'unknown_dependency_version';

    public const TYPE_UNSUPPORTED_CONSTRAINT = 'unsupported_constraint';

    public const TYPE_MIN_VERSION_VIOLATION = 'min_version_violation';

    public function __construct(
        public string $service,
        public string $type,
        public string $detail,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'type' => $this->type,
            'detail' => $this->detail,
        ];
    }
}

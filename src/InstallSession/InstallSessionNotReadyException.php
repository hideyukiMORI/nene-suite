<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use RuntimeException;

/**
 * Raised when completion preconditions are unmet (e.g. disclaimer not accepted,
 * no apps selected). Carries the Problem Details slug/title so the handler can
 * map it to a 422 with the contract-specified `type` (e.g. `disclaimer-not-accepted`).
 */
final class InstallSessionNotReadyException extends RuntimeException
{
    public function __construct(
        public readonly string $problemSlug,
        public readonly string $problemTitle,
        string $detail,
    ) {
        parent::__construct($detail);
    }
}

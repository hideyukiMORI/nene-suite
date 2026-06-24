<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The result of verifying one object's detached signature(s) against a role's keys: whether the
 * threshold of distinct valid signers was met, and — on failure — the most specific reason. Folded
 * into the {@see OriginVerificationOutcome} by {@see OriginReadModelVerifier}.
 */
final readonly class OriginJwsVerification
{
    /**
     * @param list<string> $validKids the distinct kids whose signatures verified
     */
    public function __construct(
        public bool $ok,
        public ?OriginVerificationReason $reason,
        public array $validKids,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The result of a chain verification: accept (the client may apply the pointed content) or reject
 * with a single stable {@see OriginVerificationReason} and the `stage` it failed at. `warnings`
 * carry non-fatal signals (e.g. a `warn`-state freshness notice that does not block acceptance).
 */
final readonly class OriginVerificationOutcome
{
    /**
     * @param list<string> $warnings
     */
    public function __construct(
        public bool $accepted,
        public OriginVerificationReason $reason,
        public string $stage,
        public ?OriginFreshnessState $freshness = null,
        public array $warnings = [],
        public string $message = '',
    ) {
    }

    /**
     * @param list<string> $warnings
     */
    public static function accept(string $stage, ?OriginFreshnessState $freshness, array $warnings = []): self
    {
        return new self(true, OriginVerificationReason::Ok, $stage, $freshness, $warnings);
    }

    public static function reject(
        OriginVerificationReason $reason,
        string $stage,
        string $message = '',
        ?OriginFreshnessState $freshness = null,
    ): self {
        return new self(false, $reason, $stage, $freshness, [], $message);
    }
}

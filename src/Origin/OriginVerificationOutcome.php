<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The result of a chain verification: accept (the client may apply the pointed content) or reject
 * with a single stable {@see OriginVerificationReason} and the `stage` it failed at. `warnings`
 * carry non-fatal signals (e.g. a `warn`-state freshness notice that does not block acceptance).
 * On accept, `leaf` is the **verified** trusted leaf object (the decoded targets / feed / entitlement)
 * so consumers can read its now-trusted fields (e.g. `latest.version`) without re-verifying; it is
 * null on reject. `gen` is the verified generation of the accepted tree — the consumer advances its
 * anti-rollback watermark from it (ADR 0017 §5) without re-reading `current`; it is null on reject,
 * so "there is a generation to persist" and "the walk fully verified" cannot drift apart.
 * The corpus parity gate only inspects `accepted` / `reason` / `stage`.
 */
final readonly class OriginVerificationOutcome
{
    /**
     * @param list<string>              $warnings
     * @param array<string, mixed>|null $leaf
     */
    public function __construct(
        public bool $accepted,
        public OriginVerificationReason $reason,
        public string $stage,
        public ?OriginFreshnessState $freshness = null,
        public array $warnings = [],
        public string $message = '',
        public ?array $leaf = null,
        public ?int $gen = null,
    ) {
    }

    /**
     * The same accepted outcome, carrying the verified generation. Only ever called on an accept —
     * a rejected outcome has no trusted generation to hand on.
     */
    public function withGen(int $gen): self
    {
        return new self($this->accepted, $this->reason, $this->stage, $this->freshness, $this->warnings, $this->message, $this->leaf, $gen);
    }

    /**
     * @param list<string>              $warnings
     * @param array<string, mixed>|null $leaf
     */
    public static function accept(string $stage, ?OriginFreshnessState $freshness, array $warnings = [], ?array $leaf = null): self
    {
        return new self(true, OriginVerificationReason::Ok, $stage, $freshness, $warnings, '', $leaf);
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

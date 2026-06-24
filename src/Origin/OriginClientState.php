<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;

/**
 * The per-verification client context the algorithm reads but does not trust the metadata to supply:
 * the last-applied generation it persisted (O2 watermark store), its build-time `gen` / root-version
 * floors (first-install rollback fails closed), and `now` (freshness). Modelling these explicitly is
 * what lets rollback / floor / freshness attacks be expressed as *client state vs. served bytes*.
 */
final readonly class OriginClientState
{
    public function __construct(
        public int $persistedGen,
        public int $rootVersionFloor,
        public int $genFloor,
        public DateTimeImmutable $now,
    ) {
    }

    /**
     * @param array<string, mixed> $data decoded `case.json` `client` block
     */
    public static function fromArray(array $data): self
    {
        $now = $data['now'] ?? null;

        return new self(
            (int) ($data['persisted_gen'] ?? 0),
            (int) ($data['root_version_floor'] ?? 1),
            (int) ($data['gen_floor'] ?? 1),
            new DateTimeImmutable(is_string($now) ? $now : 'now'),
        );
    }
}

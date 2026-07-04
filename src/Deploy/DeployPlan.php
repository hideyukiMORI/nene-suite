<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * The computed "update all" read model (S2-1b). `available` reflects the Origin client
 * (no verified targets → no plan, never fabricated); `enabled` mirrors the deploy-agent
 * capability flag; `executable` is true only for a non-empty, conflict-free plan.
 */
final readonly class DeployPlan
{
    public const REASON_ORIGIN_DISABLED = 'origin_disabled';

    /**
     * @param list<DeployPlanStep>     $steps
     * @param list<DeployPlanSkip>     $skipped
     * @param list<DeployPlanConflict> $conflicts
     */
    public function __construct(
        public bool $enabled,
        public bool $available,
        public ?string $reason,
        public bool $executable,
        public array $steps,
        public array $skipped,
        public array $conflicts,
    ) {
    }

    public static function unavailable(bool $enabled, string $reason): self
    {
        return new self($enabled, false, $reason, false, [], [], []);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'available' => $this->available,
            'reason' => $this->reason,
            'executable' => $this->executable,
            'steps' => array_map(static fn (DeployPlanStep $step): array => $step->toArray(), $this->steps),
            'skipped' => array_map(static fn (DeployPlanSkip $skip): array => $skip->toArray(), $this->skipped),
            'conflicts' => array_map(static fn (DeployPlanConflict $conflict): array => $conflict->toArray(), $this->conflicts),
        ];
    }
}

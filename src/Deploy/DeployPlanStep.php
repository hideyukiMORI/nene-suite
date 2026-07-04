<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * One dependency-ordered "recreate at digest" step of the update plan (ADR 0019 §3).
 */
final readonly class DeployPlanStep
{
    public function __construct(
        public int $order,
        public string $service,
        public ?string $installedVersion,
        public string $targetVersion,
        public string $imageDigest,
        public bool $forced,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'order' => $this->order,
            'service' => $this->service,
            'installedVersion' => $this->installedVersion,
            'targetVersion' => $this->targetVersion,
            'imageDigest' => $this->imageDigest,
            'forced' => $this->forced,
        ];
    }
}

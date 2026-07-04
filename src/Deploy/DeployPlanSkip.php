<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * An installed app deliberately left out of the plan. `installed_version_unknown` is
 * the defensive posture: an unverifiable app is never updated blindly (no fabrication).
 */
final readonly class DeployPlanSkip
{
    public const REASON_UP_TO_DATE = 'up_to_date';

    public const REASON_INSTALLED_VERSION_UNKNOWN = 'installed_version_unknown';

    public const REASON_UNAVAILABLE = 'unavailable';

    public function __construct(
        public string $service,
        public string $reason,
        public ?string $detail = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'service' => $this->service,
            'reason' => $this->reason,
            'detail' => $this->detail,
        ];
    }
}

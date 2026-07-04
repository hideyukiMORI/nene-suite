<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * Resolved deploy-agent capability state (ADR 0019 OQ1). `enabled` is true only when the
 * operator opted in AND supplied a strong agent key — everything else is the default
 * disabled-degrade posture ("updates visible, apply manual"). `agentKey` is empty when disabled.
 */
final readonly class DeployAgentConfig
{
    public function __construct(
        public bool $enabled,
        public string $agentKey,
    ) {
    }

    public static function disabled(): self
    {
        return new self(false, '');
    }
}

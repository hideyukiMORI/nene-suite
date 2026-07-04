<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use RuntimeException;

/**
 * Raised when a deploy-seam surface is used while the capability flag is off (the default).
 * Mapped to HTTP 409 `deploy-capability-disabled` — the disabled-degrade posture keeps
 * updates visible while the apply stays manual (ADR 0019 OQ1).
 */
final class DeployCapabilityDisabledException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The host-side deploy agent capability is not enabled on this suite.');
    }
}

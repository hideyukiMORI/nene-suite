<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * Deploy request lifecycle: `pending` until the host-side agent reports a terminal result.
 * Terminal states never transition again (the agent must not re-execute a reported request).
 */
enum DeployRequestStatus: string
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';

    public function isTerminal(): bool
    {
        return $this !== self::Pending;
    }
}

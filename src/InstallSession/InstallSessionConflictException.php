<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use RuntimeException;

/**
 * Raised when a transition is attempted on a session whose status does not allow
 * it (e.g. editing an already completed or failed session). Mapped to HTTP 409.
 */
final class InstallSessionConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $installSessionId,
        public readonly InstallSessionStatus $status,
    ) {
        parent::__construct("Install session {$installSessionId} is {$status->value} and cannot be modified.");
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use RuntimeException;

final class InstallSessionNotFoundException extends RuntimeException
{
    public function __construct(
        public readonly string $installSessionId,
    ) {
        parent::__construct("Install session {$installSessionId} was not found.");
    }
}

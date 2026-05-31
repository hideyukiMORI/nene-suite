<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseProvision;

final readonly class ProvisionAppDatabasesInput
{
    public function __construct(
        public string $installSessionId,
        public ?string $requestId = null,
        public ?string $actorUserId = null,
    ) {
    }
}

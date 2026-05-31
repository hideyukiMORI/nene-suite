<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

final readonly class WriteEnvConfigInput
{
    public function __construct(
        public string $installSessionId,
        public ?string $requestId = null,
        public ?string $actorUserId = null,
    ) {
    }
}

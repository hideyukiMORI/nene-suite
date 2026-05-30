<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

final readonly class SuiteAuditEventQuery
{
    public const DEFAULT_LIMIT = 50;
    public const MAX_LIMIT = 100;

    public function __construct(
        public int $limit = self::DEFAULT_LIMIT,
        public ?string $cursor = null,
        public ?string $installSessionId = null,
        public ?string $action = null,
    ) {
    }
}

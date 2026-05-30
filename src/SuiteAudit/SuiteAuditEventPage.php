<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

final readonly class SuiteAuditEventPage
{
    /**
     * @param list<SuiteAuditEvent> $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

final readonly class ListSuiteAuditEventsOutput
{
    public function __construct(
        public SuiteAuditEventPage $page,
    ) {
    }
}

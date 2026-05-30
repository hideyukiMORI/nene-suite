<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

final readonly class ListSuiteAuditEventsInput
{
    public function __construct(
        public SuiteAuditEventQuery $query,
    ) {
    }
}

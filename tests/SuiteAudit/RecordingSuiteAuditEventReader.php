<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteAudit;

use NeNeSuite\SuiteAudit\SuiteAuditEventPage;
use NeNeSuite\SuiteAudit\SuiteAuditEventQuery;
use NeNeSuite\SuiteAudit\SuiteAuditEventReaderInterface;

final class RecordingSuiteAuditEventReader implements SuiteAuditEventReaderInterface
{
    public ?SuiteAuditEventQuery $lastQuery = null;

    public function __construct(
        private readonly SuiteAuditEventPage $page,
    ) {
    }

    public function list(SuiteAuditEventQuery $query): SuiteAuditEventPage
    {
        $this->lastQuery = $query;

        return $this->page;
    }
}

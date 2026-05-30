<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

interface SuiteAuditRecorderInterface
{
    /**
     * Appends one audit row. Implementations MUST sanitize before/after/metadata
     * payloads per docs/explanation/audit-trail.md §5 and never overwrite rows.
     */
    public function record(RecordSuiteAuditEventCommand $command): void;
}

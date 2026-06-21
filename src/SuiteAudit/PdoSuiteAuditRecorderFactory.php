<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoSuiteAuditRecorderFactory implements SuiteAuditRecorderFactoryInterface
{
    public function __construct(
        private SuiteAuditSanitizer $sanitizer,
    ) {
    }

    public function create(DatabaseQueryExecutorInterface $query): SuiteAuditRecorderInterface
    {
        return new PdoSuiteAuditRecorder($query, $this->sanitizer);
    }
}

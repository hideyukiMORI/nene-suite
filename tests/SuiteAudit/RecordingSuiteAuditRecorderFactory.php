<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteAudit;

use Nene2\Database\DatabaseQueryExecutorInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Returns a shared recording recorder, ignoring the executor — there is no real
 * transaction in unit tests.
 */
final readonly class RecordingSuiteAuditRecorderFactory implements SuiteAuditRecorderFactoryInterface
{
    public function __construct(
        private SuiteAuditRecorderInterface $recorder,
    ) {
    }

    public function create(DatabaseQueryExecutorInterface $query): SuiteAuditRecorderInterface
    {
        return $this->recorder;
    }
}

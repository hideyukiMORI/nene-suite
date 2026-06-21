<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use Nene2\Database\DatabaseQueryExecutorInterface;

/**
 * Builds a {@see SuiteAuditRecorderInterface} bound to a specific query executor so
 * the audit row is written on the same connection — and therefore inside the same
 * transaction — as the mutation it records (ADR 0007 §5). See
 * {@see \NeNeSuite\Auth\OperatorRepositoryFactoryInterface} for the rationale.
 */
interface SuiteAuditRecorderFactoryInterface
{
    public function create(DatabaseQueryExecutorInterface $query): SuiteAuditRecorderInterface;
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteAudit;

use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;

/**
 * Test double that captures recorded audit commands for assertions.
 */
final class RecordingSuiteAuditRecorder implements SuiteAuditRecorderInterface
{
    /** @var list<RecordSuiteAuditEventCommand> */
    public array $commands = [];

    public function record(RecordSuiteAuditEventCommand $command): void
    {
        $this->commands[] = $command;
    }

    public function lastCommand(): ?RecordSuiteAuditEventCommand
    {
        return $this->commands[array_key_last($this->commands)] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteAudit;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\SuiteAudit\PdoSuiteAuditEventReader;
use NeNeSuite\SuiteAudit\PdoSuiteAuditRecorder;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditEventQuery;
use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use PHPUnit\Framework\TestCase;

final class PdoSuiteAuditEventReaderTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const SESSION_A = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const SESSION_B = '01J8XR4ZS6Q9V2H7K3N5M0B999';

    private PdoDatabaseQueryExecutor $executor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->executor = new PdoDatabaseQueryExecutor(new PdoConnectionFactory(new DatabaseConfig(
            null,
            'test',
            'sqlite',
            'localhost',
            1,
            ':memory:',
            'nene-suite-test',
            '',
            'utf8',
        )));

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/suite_audit_events.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }

        $recorder = new PdoSuiteAuditRecorder($this->executor, new SuiteAuditSanitizer());
        // Recorded oldest → newest; ULID ids are monotonic so id DESC == newest first.
        $recorder->record($this->command('install_session.started', self::SESSION_A));
        $recorder->record($this->command('app_selection.changed', self::SESSION_A));
        $recorder->record($this->command('install_session.started', self::SESSION_B));
        $recorder->record($this->command('disclaimer.accepted', self::SESSION_A));
    }

    public function testListsNewestFirst(): void
    {
        $page = (new PdoSuiteAuditEventReader($this->executor))->list(new SuiteAuditEventQuery());

        self::assertCount(4, $page->items);
        self::assertSame('disclaimer.accepted', $page->items[0]->action);
        self::assertSame('install_session.started', $page->items[3]->action);
        self::assertNull($page->nextCursor);
    }

    public function testPaginatesWithCursor(): void
    {
        $reader = new PdoSuiteAuditEventReader($this->executor);

        $first = $reader->list(new SuiteAuditEventQuery(limit: 2));
        self::assertCount(2, $first->items);
        self::assertNotNull($first->nextCursor);

        $second = $reader->list(new SuiteAuditEventQuery(limit: 2, cursor: $first->nextCursor));
        self::assertCount(2, $second->items);
        self::assertNull($second->nextCursor);

        $ids = array_merge(
            array_map(static fn ($e): string => $e->id, $first->items),
            array_map(static fn ($e): string => $e->id, $second->items),
        );
        self::assertCount(4, array_unique($ids));
    }

    public function testFiltersByInstallSessionId(): void
    {
        $page = (new PdoSuiteAuditEventReader($this->executor))->list(new SuiteAuditEventQuery(installSessionId: self::SESSION_B));

        self::assertCount(1, $page->items);
        self::assertSame(self::SESSION_B, $page->items[0]->installSessionId);
    }

    public function testFiltersByAction(): void
    {
        $page = (new PdoSuiteAuditEventReader($this->executor))->list(new SuiteAuditEventQuery(action: 'install_session.started'));

        self::assertCount(2, $page->items);
        foreach ($page->items as $event) {
            self::assertSame('install_session.started', $event->action);
        }
    }

    private function command(string $action, string $sessionId): RecordSuiteAuditEventCommand
    {
        return new RecordSuiteAuditEventCommand(
            suiteId: self::SUITE_ID,
            action: $action,
            entityType: 'install_session',
            entityId: $sessionId,
            afterJson: ['status' => 'in_progress'],
            installSessionId: $sessionId,
        );
    }
}

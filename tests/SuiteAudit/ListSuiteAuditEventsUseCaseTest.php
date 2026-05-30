<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteAudit;

use NeNeSuite\SuiteAudit\ListSuiteAuditEventsInput;
use NeNeSuite\SuiteAudit\ListSuiteAuditEventsUseCase;
use NeNeSuite\SuiteAudit\SuiteAuditEvent;
use NeNeSuite\SuiteAudit\SuiteAuditEventPage;
use NeNeSuite\SuiteAudit\SuiteAuditEventQuery;
use PHPUnit\Framework\TestCase;

final class ListSuiteAuditEventsUseCaseTest extends TestCase
{
    public function testPassesQueryThroughAndReturnsPage(): void
    {
        $event = new SuiteAuditEvent(
            id: '01J8XRADTQ9V2H7K3N5M0B8QGH',
            suiteId: '01J8XRDEV000000000000000ZA',
            orgExternalId: null,
            actorUserId: null,
            actorLabel: 'operator@example.com',
            action: 'disclaimer.accepted',
            entityType: 'disclaimer_acknowledgment',
            entityId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            before: null,
            after: ['disclaimerVersion' => '2026-05-29'],
            createdAt: '2026-05-30T09:50:00Z',
            source: 'installer_ui',
            requestId: null,
            installSessionId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            metadata: null,
        );
        $reader = new RecordingSuiteAuditEventReader(new SuiteAuditEventPage([$event], 'cursor-123'));

        $useCase = new ListSuiteAuditEventsUseCase($reader);
        $query = new SuiteAuditEventQuery(limit: 25, cursor: null, installSessionId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC', action: 'disclaimer.accepted');
        $output = $useCase->execute(new ListSuiteAuditEventsInput($query));

        self::assertSame($query, $reader->lastQuery);
        self::assertCount(1, $output->page->items);
        self::assertSame('cursor-123', $output->page->nextCursor);
        self::assertSame('disclaimer.accepted', $output->page->items[0]->action);
    }
}

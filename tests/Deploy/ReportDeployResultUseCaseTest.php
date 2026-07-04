<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use NeNeSuite\Deploy\DeployRequest;
use NeNeSuite\Deploy\DeployRequestConflictException;
use NeNeSuite\Deploy\DeployRequestNotFoundException;
use NeNeSuite\Deploy\DeployRequestStatus;
use NeNeSuite\Deploy\DeployValidationException;
use NeNeSuite\Deploy\ReportDeployResultInput;
use NeNeSuite\Deploy\ReportDeployResultUseCase;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class ReportDeployResultUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const REQUEST_ID = '01J8XRDRQ000000000000000ZA';

    public function testRecordsSucceededResultAndAudits(): void
    {
        $repository = new InMemoryDeployRequestRepository();
        $repository->insert($this->pendingRequest());
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($repository, $recorder)->execute(new ReportDeployResultInput(
            self::REQUEST_ID,
            DeployRequestStatus::Succeeded,
            'recreated at digest',
        ));

        $request = $output->request;
        self::assertSame(DeployRequestStatus::Succeeded, $request->status);
        self::assertSame('recreated at digest', $request->detail);
        self::assertNotNull($request->completedAt);

        $stored = $repository->findById(self::REQUEST_ID);
        self::assertNotNull($stored);
        self::assertSame(DeployRequestStatus::Succeeded, $stored->status);

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('deploy_request.completed', $event->action);
        self::assertSame('deploy_request', $event->entityType);
        self::assertNotNull($event->beforeJson);
        self::assertSame('pending', $event->beforeJson['status']);
        self::assertNotNull($event->afterJson);
        self::assertSame('succeeded', $event->afterJson['status']);
        self::assertNull($event->actorUserId);
        self::assertSame('deploy-agent', $event->actorLabel);
        self::assertSame('api', $event->source);
    }

    public function testRecordsFailedResult(): void
    {
        $repository = new InMemoryDeployRequestRepository();
        $repository->insert($this->pendingRequest());

        $output = $this->useCase($repository)->execute(new ReportDeployResultInput(
            self::REQUEST_ID,
            DeployRequestStatus::Failed,
            'compose pull failed',
        ));

        self::assertSame(DeployRequestStatus::Failed, $output->request->status);
        self::assertSame('compose pull failed', $output->request->detail);
    }

    public function testRejectsNonTerminalStatus(): void
    {
        $useCase = $this->useCase(new InMemoryDeployRequestRepository());

        $this->expectException(DeployValidationException::class);

        $useCase->execute(new ReportDeployResultInput(self::REQUEST_ID, DeployRequestStatus::Pending, null));
    }

    public function testThrowsNotFoundForUnknownId(): void
    {
        $useCase = $this->useCase(new InMemoryDeployRequestRepository());

        $this->expectException(DeployRequestNotFoundException::class);

        $useCase->execute(new ReportDeployResultInput(self::REQUEST_ID, DeployRequestStatus::Succeeded, null));
    }

    public function testConflictsWhenAlreadyTerminal(): void
    {
        $repository = new InMemoryDeployRequestRepository();
        $repository->insert($this->pendingRequest());
        $useCase = $this->useCase($repository);

        $useCase->execute(new ReportDeployResultInput(self::REQUEST_ID, DeployRequestStatus::Succeeded, null));

        $this->expectException(DeployRequestConflictException::class);

        $useCase->execute(new ReportDeployResultInput(self::REQUEST_ID, DeployRequestStatus::Failed, null));
    }

    private function useCase(
        InMemoryDeployRequestRepository $repository,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): ReportDeployResultUseCase {
        return new ReportDeployResultUseCase(
            new ImmediateTransactionManager(),
            new InMemoryDeployRequestRepositoryFactory($repository),
            new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            self::SUITE_ID,
        );
    }

    private function pendingRequest(): DeployRequest
    {
        return new DeployRequest(
            id: self::REQUEST_ID,
            service: 'nene-invoice',
            imageDigest: 'sha256:9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08',
            status: DeployRequestStatus::Pending,
            requestedBy: '01J8XRDOP000000000000000ZA',
            detail: null,
            createdAt: '2026-07-04T09:00:00Z',
            updatedAt: '2026-07-04T09:00:00Z',
            completedAt: null,
        );
    }
}

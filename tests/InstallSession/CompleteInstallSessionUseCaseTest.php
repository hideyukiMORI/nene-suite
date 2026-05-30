<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\InstallManifest\InstallManifestFactory;
use NeNeSuite\InstallSession\CompleteInstallSessionInput;
use NeNeSuite\InstallSession\CompleteInstallSessionUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionNotReadyException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\Tests\InstallManifest\InMemoryInstallManifestRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class CompleteInstallSessionUseCaseTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XRDEV0FED0000000000ZAB';

    public function testCompletesWritesManifestAndAuditsBoth(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->readySession());
        $manifests = new InMemoryInstallManifestRepository();
        $recorder = new RecordingSuiteAuditRecorder();

        $useCase = $this->useCase($sessions, $manifests, $recorder);
        $output = $useCase->execute(new CompleteInstallSessionInput(self::SESSION_ID));

        self::assertSame(InstallSessionStatus::Completed, $output->session->status);
        self::assertNotNull($output->session->completedAt);
        self::assertNotNull($output->session->installManifestId);
        self::assertSame(self::ORG_ID, $output->session->orgExternalId);

        self::assertCount(1, $manifests->manifests);
        $manifest = $manifests->findById($output->session->installManifestId);
        self::assertNotNull($manifest);
        self::assertSame(self::SUITE_ID, $manifest->body['suite_id']);

        $actions = array_map(static fn ($c): string => $c->action, $recorder->commands);
        self::assertSame(['manifest.created', 'install_session.completed'], $actions);

        $manifestEvent = $recorder->commands[0];
        self::assertSame('install_manifest', $manifestEvent->entityType);
        self::assertNull($manifestEvent->beforeJson);
        self::assertNotNull($manifestEvent->afterJson);
        self::assertSame(self::ORG_ID, $manifestEvent->afterJson['org_external_id']);

        $completedEvent = $recorder->commands[1];
        self::assertNotNull($completedEvent->beforeJson);
        self::assertSame('in_progress', $completedEvent->beforeJson['status']);
        self::assertNotNull($completedEvent->afterJson);
        self::assertSame('completed', $completedEvent->afterJson['status']);
    }

    public function testThrowsWhenDisclaimerNotAccepted(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->readySession(disclaimerAccepted: false));

        try {
            $this->useCase($sessions)->execute(new CompleteInstallSessionInput(self::SESSION_ID));
            self::fail('Expected InstallSessionNotReadyException.');
        } catch (InstallSessionNotReadyException $exception) {
            self::assertSame('disclaimer-not-accepted', $exception->problemSlug);
        }
    }

    public function testThrowsWhenNoAppsSelected(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->readySession(selectedApps: []));

        try {
            $this->useCase($sessions)->execute(new CompleteInstallSessionInput(self::SESSION_ID));
            self::fail('Expected InstallSessionNotReadyException.');
        } catch (InstallSessionNotReadyException $exception) {
            self::assertSame('no-apps-selected', $exception->problemSlug);
        }
    }

    public function testThrowsConflictWhenNotInProgress(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->readySession(status: InstallSessionStatus::Completed));

        $this->expectException(InstallSessionConflictException::class);

        $this->useCase($sessions)->execute(new CompleteInstallSessionInput(self::SESSION_ID));
    }

    public function testThrowsNotFoundWhenMissing(): void
    {
        $this->expectException(InstallSessionNotFoundException::class);

        $this->useCase(new InMemoryInstallSessionRepository())->execute(new CompleteInstallSessionInput(self::SESSION_ID));
    }

    private function useCase(
        InMemoryInstallSessionRepository $sessions,
        ?InMemoryInstallManifestRepository $manifests = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): CompleteInstallSessionUseCase {
        return new CompleteInstallSessionUseCase(
            $sessions,
            $manifests ?? new InMemoryInstallManifestRepository(),
            new InstallManifestFactory(),
            $recorder ?? new RecordingSuiteAuditRecorder(),
            self::SUITE_ID,
            self::ORG_ID,
        );
    }

    /**
     * @param list<string> $selectedApps
     */
    private function readySession(
        InstallSessionStatus $status = InstallSessionStatus::InProgress,
        bool $disclaimerAccepted = true,
        array $selectedApps = ['nene-invoice'],
    ): InstallSession {
        return new InstallSession(
            id: self::SESSION_ID,
            suiteId: self::SUITE_ID,
            status: $status,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: $selectedApps,
            disclaimerAccepted: $disclaimerAccepted,
            disclaimerAcceptedAt: $disclaimerAccepted ? '2026-05-30T09:50:00Z' : null,
            orgExternalId: null,
            orgDisplayName: 'Example Organization',
            installManifestId: null,
            failureCode: null,
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
            completedAt: null,
        );
    }
}

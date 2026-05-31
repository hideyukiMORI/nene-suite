<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteEnv;

use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\SuiteEnv\SuiteEnvConfigFactory;
use NeNeSuite\SuiteEnv\WriteEnvConfigInput;
use NeNeSuite\SuiteEnv\WriteEnvConfigUseCase;
use NeNeSuite\Tests\InstalledApps\FixedSuiteAppUrlReader;
use NeNeSuite\Tests\InstallSession\InMemoryInstallSessionRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class WriteEnvConfigUseCaseTest extends TestCase
{
    private const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC';
    private const SUITE_ID   = '01J8XRDEV000000000000000ZA';
    private const ORG_ID     = '01J8XRDEV0FED0000000000ZAB';
    private const BASE_URL   = 'https://ops.example.com/';

    public function testWritesEnvAndAuditsEnvConfigWritten(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->completedSession());
        $writer = new CapturingSuiteEnvWriter();
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($sessions, $writer, $recorder)
            ->execute(new WriteEnvConfigInput(self::SESSION_ID));

        self::assertNotNull($writer->written);
        self::assertSame('1', $writer->written->toEnvMap()['NENE_SUITE_MODE']);
        self::assertSame(self::SUITE_ID, $writer->written->toEnvMap()['NENE_SUITE_ID']);
        self::assertSame(self::BASE_URL, $writer->written->toEnvMap()['NENE_SUITE_BASE_URL']);
        self::assertSame(self::ORG_ID, $writer->written->toEnvMap()['NENE_SUITE_ORG_EXTERNAL_ID']);
        self::assertSame('nene-invoice', $writer->written->toEnvMap()['NENE_SUITE_INSTALLED_APPS']);
        self::assertNotEmpty($writer->written->toEnvMap()['NENE_SUITE_JWT_SECRET']);

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('env_config.written', $event->action);
        self::assertSame('suite_env_config', $event->entityType);
        self::assertSame(self::SUITE_ID, $event->entityId);
        self::assertNull($event->beforeJson);
        self::assertNotNull($event->afterJson);
        self::assertSame('[REDACTED]', $event->afterJson['NENE_SUITE_JWT_SECRET']);
        self::assertSame(self::SESSION_ID, $event->installSessionId);

        self::assertSame('[REDACTED]', $output->sanitizedEnvMap['NENE_SUITE_JWT_SECRET']);
    }

    public function testPopulatesAppUrlsForConfiguredApps(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->completedSession());
        $writer = new CapturingSuiteEnvWriter();

        $useCase = $this->useCase(
            $sessions,
            $writer,
            null,
            ['nene-invoice' => 'https://ops.example.com/nene-invoice/'],
        );
        $useCase->execute(new WriteEnvConfigInput(self::SESSION_ID));

        self::assertNotNull($writer->written);
        $envMap = $writer->written->toEnvMap();
        self::assertSame('https://ops.example.com/nene-invoice/', $envMap['NENE_SUITE_APP_NENE_INVOICE_URL']);
    }

    public function testIssuerUrlDerivedFromBaseUrl(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $sessions->save($this->completedSession());
        $writer = new CapturingSuiteEnvWriter();

        $this->useCase($sessions, $writer)->execute(new WriteEnvConfigInput(self::SESSION_ID));

        self::assertNotNull($writer->written);
        self::assertSame(
            'https://ops.example.com/api/auth',
            $writer->written->toEnvMap()['NENE_SUITE_ISSUER_URL'],
        );
    }

    public function testThrowsWhenSessionNotFound(): void
    {
        $this->expectException(InstallSessionNotFoundException::class);

        $this->useCase(new InMemoryInstallSessionRepository())
            ->execute(new WriteEnvConfigInput(self::SESSION_ID));
    }

    /**
     * @param array<string, string> $urls
     */
    private function useCase(
        InMemoryInstallSessionRepository $sessions,
        ?CapturingSuiteEnvWriter $writer = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
        array $urls = [],
    ): WriteEnvConfigUseCase {
        return new WriteEnvConfigUseCase(
            sessions: $sessions,
            factory: new SuiteEnvConfigFactory(),
            writer: $writer ?? new CapturingSuiteEnvWriter(),
            audit: $recorder ?? new RecordingSuiteAuditRecorder(),
            urls: new FixedSuiteAppUrlReader($urls),
            suiteId: self::SUITE_ID,
            baseUrl: self::BASE_URL,
        );
    }

    private function completedSession(): InstallSession
    {
        return new InstallSession(
            id: self::SESSION_ID,
            suiteId: self::SUITE_ID,
            status: InstallSessionStatus::Completed,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: ['nene-invoice'],
            disclaimerAccepted: true,
            disclaimerAcceptedAt: '2026-05-31T10:00:00Z',
            orgExternalId: self::ORG_ID,
            orgDisplayName: 'Example Organization',
            installManifestId: '01J8XRMANIFEST00000000000A',
            failureCode: null,
            createdAt: '2026-05-31T09:58:00Z',
            updatedAt: '2026-05-31T10:00:00Z',
            completedAt: '2026-05-31T10:00:00Z',
        );
    }
}

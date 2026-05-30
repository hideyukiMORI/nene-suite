<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use NeNeSuite\InstallSession\GetInstallSessionInput;
use NeNeSuite\InstallSession\GetInstallSessionUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use PHPUnit\Framework\TestCase;

final class GetInstallSessionUseCaseTest extends TestCase
{
    public function testReturnsExistingSession(): void
    {
        $sessions = new InMemoryInstallSessionRepository();
        $session = $this->session('01J8XR4ZS6Q9V2H7K3N5M0B8TC');
        $sessions->save($session);

        $useCase = new GetInstallSessionUseCase($sessions);
        $output = $useCase->execute(new GetInstallSessionInput('01J8XR4ZS6Q9V2H7K3N5M0B8TC'));

        self::assertSame($session, $output->session);
    }

    public function testThrowsWhenMissing(): void
    {
        $useCase = new GetInstallSessionUseCase(new InMemoryInstallSessionRepository());

        $this->expectException(InstallSessionNotFoundException::class);

        $useCase->execute(new GetInstallSessionInput('01J8XR4ZS6Q9V2H7K3N5M0B8TC'));
    }

    private function session(string $id): InstallSession
    {
        return new InstallSession(
            id: $id,
            suiteId: '01J8XRDEV000000000000000ZA',
            status: InstallSessionStatus::InProgress,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: [],
            disclaimerAccepted: false,
            disclaimerAcceptedAt: null,
            orgExternalId: null,
            orgDisplayName: null,
            installManifestId: null,
            failureCode: null,
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
            completedAt: null,
        );
    }
}

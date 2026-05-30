<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallSession;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\InstallSession\PdoInstallSessionRepository;
use PHPUnit\Framework\TestCase;

final class PdoInstallSessionRepositoryTest extends TestCase
{
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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/install_sessions.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveAndFindByIdRoundTrip(): void
    {
        $repository = new PdoInstallSessionRepository($this->executor);

        $session = new InstallSession(
            id: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            suiteId: '01J8XRDEV000000000000000ZA',
            status: InstallSessionStatus::InProgress,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: ['nene-invoice', 'nene-clear'],
            disclaimerAccepted: false,
            disclaimerAcceptedAt: null,
            orgExternalId: null,
            orgDisplayName: 'Example Organization',
            installManifestId: null,
            failureCode: null,
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
            completedAt: null,
        );

        $repository->save($session);
        $found = $repository->findById('01J8XR4ZS6Q9V2H7K3N5M0B8TC');

        self::assertNotNull($found);
        self::assertSame('01J8XR4ZS6Q9V2H7K3N5M0B8TC', $found->id);
        self::assertSame(InstallSessionStatus::InProgress, $found->status);
        self::assertSame(InstallTier::B, $found->tier);
        self::assertSame(['nene-invoice', 'nene-clear'], $found->selectedApps);
        self::assertFalse($found->disclaimerAccepted);
        self::assertSame('Example Organization', $found->orgDisplayName);
        self::assertSame('2026-05-30T09:48:46Z', $found->createdAt);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $repository = new PdoInstallSessionRepository($this->executor);

        self::assertNull($repository->findById('01J8XR4ZS6Q9V2H7K3N5M0B8TC'));
    }

    public function testUpdatePersistsChangedSelection(): void
    {
        $repository = new PdoInstallSessionRepository($this->executor);

        $session = new InstallSession(
            id: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
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
        $repository->save($session);

        $repository->update($session->withSelectedApps(['nene-invoice', 'nene-clear'], '2026-05-30T10:00:00Z'));

        $found = $repository->findById('01J8XR4ZS6Q9V2H7K3N5M0B8TC');
        self::assertNotNull($found);
        self::assertSame(['nene-invoice', 'nene-clear'], $found->selectedApps);
        self::assertSame('2026-05-30T10:00:00Z', $found->updatedAt);
    }
}

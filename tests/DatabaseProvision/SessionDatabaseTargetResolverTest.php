<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use NeNeSuite\DatabaseProvision\AppDatabaseNamer;
use NeNeSuite\DatabaseProvision\DatabaseTarget;
use NeNeSuite\DatabaseProvision\DatabaseTargetFactory;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use NeNeSuite\DatabaseProvision\ExternalProvisionNotSupportedException;
use NeNeSuite\DatabaseProvision\SessionDatabaseTargetResolver;
use NeNeSuite\InstallSession\AppDatabaseTargetSelection;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use PHPUnit\Framework\TestCase;

final class SessionDatabaseTargetResolverTest extends TestCase
{
    public function testSessionOverrideWinsOverFallback(): void
    {
        $session = $this->session([
            new AppDatabaseTargetSelection('nene-invoice', DatabaseTargetMode::Adopt, 'legacy-db.internal', 'invoice_prod'),
        ]);

        $target = $this->resolver()->resolve($session, 'nene-invoice');

        self::assertSame(DatabaseTargetMode::Adopt, $target->mode);
        self::assertSame('invoice_prod', $target->databaseName);
        self::assertSame('legacy-db.internal', $target->server);
    }

    public function testFallsBackToEnvResolverWhenNoOverride(): void
    {
        // The fallback returns an adopt target for nene-clear; nene-invoice has no override
        // and so resolves to the fallback's default provision target.
        $fallback = (new FixedDatabaseTargetResolver())->with(
            new DatabaseTarget('nene-clear', DatabaseTargetMode::Adopt, 'clear_legacy', 'clear-db.internal'),
        );
        $resolver = new SessionDatabaseTargetResolver($fallback, new DatabaseTargetFactory(new AppDatabaseNamer()));

        $invoice = $resolver->resolve($this->session([]), 'nene-invoice');
        self::assertSame(DatabaseTargetMode::Provision, $invoice->mode);
        self::assertSame('nene_invoice', $invoice->databaseName);

        $clear = $resolver->resolve($this->session([]), 'nene-clear');
        self::assertSame(DatabaseTargetMode::Adopt, $clear->mode);
        self::assertSame('clear-db.internal', $clear->server);
    }

    public function testValidatesSessionOverride(): void
    {
        // A provision override on an external server is refused (ADR 0021 OQ2), the same
        // guard the env path applies — even though it arrived through the session.
        $session = $this->session([
            new AppDatabaseTargetSelection('nene-invoice', DatabaseTargetMode::Provision, 'other-db.internal', null),
        ]);

        $this->expectException(ExternalProvisionNotSupportedException::class);

        $this->resolver()->resolve($session, 'nene-invoice');
    }

    private function resolver(): SessionDatabaseTargetResolver
    {
        return new SessionDatabaseTargetResolver(
            new FixedDatabaseTargetResolver(),
            new DatabaseTargetFactory(new AppDatabaseNamer()),
        );
    }

    /**
     * @param list<AppDatabaseTargetSelection> $databaseTargets
     */
    private function session(array $databaseTargets): InstallSession
    {
        return new InstallSession(
            id: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            suiteId: '01J8XRDEV000000000000000ZA',
            status: InstallSessionStatus::InProgress,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: ['nene-invoice', 'nene-clear'],
            disclaimerAccepted: false,
            disclaimerAcceptedAt: null,
            orgExternalId: null,
            orgDisplayName: null,
            installManifestId: null,
            failureCode: null,
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
            completedAt: null,
            databaseTargets: $databaseTargets,
        );
    }
}

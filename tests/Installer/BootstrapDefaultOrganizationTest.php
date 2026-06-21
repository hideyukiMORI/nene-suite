<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Installer;

use Nene2\Testing\DatabaseTestKit;
use NeNeSuite\Installer\BootstrapDefaultOrganizationInput;
use NeNeSuite\Installer\BootstrapDefaultOrganizationUseCase;
use NeNeSuite\InstallSession\InstallSession;
use NeNeSuite\InstallSession\InstallSessionStatus;
use NeNeSuite\InstallSession\InstallTier;
use NeNeSuite\InstallSession\PdoInstallSessionRepository;
use NeNeSuite\SuiteAudit\PdoSuiteAuditRecorderFactory;
use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use NeNeSuite\Tenancy\CreateOrganizationUseCase;
use NeNeSuite\Tenancy\GrantMembershipUseCase;
use NeNeSuite\Tenancy\PdoMembershipRepository;
use NeNeSuite\Tenancy\PdoMembershipRepositoryFactory;
use NeNeSuite\Tenancy\PdoOrganizationRepository;
use NeNeSuite\Tenancy\PdoOrganizationRepositoryFactory;
use NeNeSuite\Tenancy\Role;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the A4 installer bootstrap against a real SQLite database: default org
 * find-or-create, session org_external_id stamping, the dual superadmin+admin grant,
 * idempotent re-run, and the Japanese-name slug fallback.
 */
final class BootstrapDefaultOrganizationTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const SESSION_ID = '01J8XRDSESSION0000000000ZA';
    private const OPERATOR_ID = '01J8XRDOP000000000000000ZA';

    private string $dbPath;
    private DatabaseTestKit $kit;
    private PdoInstallSessionRepository $sessions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('nene-suite-boot-', true) . '.sqlite';
        $this->kit = DatabaseTestKit::sqlite($this->dbPath);

        foreach (['organizations', 'memberships', 'install_sessions', 'suite_audit_events'] as $schema) {
            $this->loadSchema($schema);
        }

        $this->sessions = new PdoInstallSessionRepository($this->kit->queryExecutor);
        $this->sessions->save($this->session());
    }

    protected function tearDown(): void
    {
        if (is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        parent::tearDown();
    }

    public function testCreatesOrgStampsSessionAndGrantsSuperadminAndOrgAdmin(): void
    {
        $output = $this->bootstrap()->execute(
            new BootstrapDefaultOrganizationInput(self::SESSION_ID, 'Acme KK', self::OPERATOR_ID),
        );

        $organization = $output->organization;
        self::assertSame('acme-kk', $organization->slug);

        $session = $this->sessions->findById(self::SESSION_ID);
        self::assertNotNull($session);
        self::assertSame($organization->externalId, $session->orgExternalId, 'session carries the minted external_id');
        self::assertNotSame(self::SUITE_ID, $session->orgExternalId, 'no longer the suiteId fallback');

        $memberships = new PdoMembershipRepository($this->kit->queryExecutor);
        $platform = $memberships->findByOperatorAndOrganization(self::OPERATOR_ID, null);
        $orgMembership = $memberships->findByOperatorAndOrganization(self::OPERATOR_ID, $organization->id);

        self::assertNotNull($platform);
        self::assertSame(Role::Superadmin, $platform->role);
        self::assertNotNull($orgMembership);
        self::assertSame(Role::Admin, $orgMembership->role);
    }

    public function testIsIdempotentOnReRun(): void
    {
        $bootstrap = $this->bootstrap();
        $first = $bootstrap->execute(new BootstrapDefaultOrganizationInput(self::SESSION_ID, 'Acme KK', self::OPERATOR_ID));
        $second = $bootstrap->execute(new BootstrapDefaultOrganizationInput(self::SESSION_ID, 'Acme KK', self::OPERATOR_ID));

        self::assertSame($first->organization->id, $second->organization->id, 're-run reuses the existing org');
        self::assertSame(1, $this->countRows('organizations'));
        self::assertSame(2, $this->countRows('memberships'), 'superadmin + admin only — no duplicates');
    }

    public function testDerivesFallbackSlugForJapaneseOrgName(): void
    {
        $output = $this->bootstrap()->execute(
            new BootstrapDefaultOrganizationInput(self::SESSION_ID, 'ねね商店', self::OPERATOR_ID),
        );

        self::assertMatchesRegularExpression('/^org-[0-9a-f]{12}$/', $output->organization->slug);
    }

    private function bootstrap(): BootstrapDefaultOrganizationUseCase
    {
        $audit = new PdoSuiteAuditRecorderFactory(new SuiteAuditSanitizer());

        return new BootstrapDefaultOrganizationUseCase(
            $this->sessions,
            new CreateOrganizationUseCase($this->kit->transactionManager, new PdoOrganizationRepositoryFactory(), $audit, self::SUITE_ID),
            new PdoOrganizationRepository($this->kit->queryExecutor),
            new GrantMembershipUseCase($this->kit->transactionManager, new PdoMembershipRepositoryFactory(), $audit, self::SUITE_ID),
            new PdoMembershipRepository($this->kit->queryExecutor),
            self::SUITE_ID,
        );
    }

    private function session(): InstallSession
    {
        $now = '2026-06-21T00:00:00Z';

        return new InstallSession(
            id: self::SESSION_ID,
            suiteId: self::SUITE_ID,
            status: InstallSessionStatus::InProgress,
            tier: InstallTier::B,
            catalogRevision: 1,
            selectedApps: ['nene-invoice'],
            disclaimerAccepted: true,
            disclaimerAcceptedAt: $now,
            orgExternalId: null,
            orgDisplayName: 'Acme KK',
            installManifestId: null,
            failureCode: null,
            createdAt: $now,
            updatedAt: $now,
            completedAt: null,
        );
    }

    private function loadSchema(string $name): void
    {
        $schema = (string) file_get_contents(dirname(__DIR__, 2) . "/database/schema/{$name}.sql");

        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);

            if ($statement !== '') {
                $this->kit->queryExecutor->execute($statement);
            }
        }
    }

    private function countRows(string $table): int
    {
        $row = $this->kit->queryExecutor->fetchOne("SELECT COUNT(*) AS c FROM {$table}");

        return (int) ($row['c'] ?? 0);
    }
}

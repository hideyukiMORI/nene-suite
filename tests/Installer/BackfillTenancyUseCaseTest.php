<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Installer;

use Nene2\Testing\DatabaseTestKit;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\PdoOperatorRepository;
use NeNeSuite\Installer\BackfillTenancyInput;
use NeNeSuite\Installer\BackfillTenancyUseCase;
use NeNeSuite\InstallManifest\PdoInstallManifestRepository;
use NeNeSuite\InstallSession\PdoInstallSessionRepository;
use NeNeSuite\SuiteAudit\PdoSuiteAuditRecorderFactory;
use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use NeNeSuite\Tenancy\CreateOrganizationUseCase;
use NeNeSuite\Tenancy\GrantMembershipUseCase;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\PdoMembershipRepository;
use NeNeSuite\Tenancy\PdoMembershipRepositoryFactory;
use NeNeSuite\Tenancy\PdoOrganizationRepository;
use NeNeSuite\Tenancy\PdoOrganizationRepositoryFactory;
use NeNeSuite\Tenancy\Role;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * A5 backfill against a real SQLite database: fresh-DB no-op, pre-A4 multi-operator backfill
 * (oldest = superadmin+admin, rest = admin), idempotency, A4-installed reuse, and external_id
 * reconciliation with NENE_SUITE_ORG_EXTERNAL_ID.
 */
final class BackfillTenancyUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ENV_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';

    private string $dbPath;
    private DatabaseTestKit $kit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('nene-suite-bf-', true) . '.sqlite';
        $this->kit = DatabaseTestKit::sqlite($this->dbPath);

        foreach (['operators', 'organizations', 'memberships', 'install_sessions', 'install_manifests', 'suite_audit_events'] as $schema) {
            $this->loadSchema($schema);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        parent::tearDown();
    }

    public function testFreshDatabaseWithNoOperatorsIsANoOp(): void
    {
        $output = $this->useCase(self::ENV_EXTERNAL_ID)->execute(new BackfillTenancyInput());

        self::assertTrue($output->skipped);
        self::assertNull($output->organization);
        self::assertSame(0, $this->countRows('organizations'));
        self::assertSame(0, $this->countRows('memberships'));
    }

    public function testBackfillsMultipleOperatorsWithOldestAsSuperadmin(): void
    {
        $this->seedOperator('01J0OP100000000000000000ZA', '2026-01-01T00:00:00Z', 'first@example.com');
        $this->seedOperator('01J0OP200000000000000000ZA', '2026-02-01T00:00:00Z', 'second@example.com');
        $this->seedOperator('01J0OP300000000000000000ZA', '2026-03-01T00:00:00Z', 'third@example.com');

        $output = $this->useCase(self::ENV_EXTERNAL_ID)->execute(new BackfillTenancyInput());

        self::assertFalse($output->skipped);
        self::assertSame(3, $output->operatorsBackfilled);
        self::assertSame(1, $this->countRows('organizations'), 'exactly one default org');
        $org = $output->organization;
        self::assertNotNull($org);

        $memberships = new PdoMembershipRepository($this->kit->queryExecutor);
        // Oldest operator: superadmin (null org) + admin (org).
        self::assertSame(Role::Superadmin, $memberships->findByOperatorAndOrganization('01J0OP100000000000000000ZA', null)?->role);
        self::assertSame(Role::Admin, $memberships->findByOperatorAndOrganization('01J0OP100000000000000000ZA', $org->id)?->role);
        // The others: org admin only, no platform superadmin.
        self::assertNull($memberships->findByOperatorAndOrganization('01J0OP200000000000000000ZA', null));
        self::assertSame(Role::Admin, $memberships->findByOperatorAndOrganization('01J0OP200000000000000000ZA', $org->id)?->role);
        self::assertNull($memberships->findByOperatorAndOrganization('01J0OP300000000000000000ZA', null));
        self::assertSame(Role::Admin, $memberships->findByOperatorAndOrganization('01J0OP300000000000000000ZA', $org->id)?->role);

        self::assertSame(1, $this->countWhere('memberships', 'organization_id IS NULL'), 'exactly one platform superadmin');
        self::assertSame(4, $this->countRows('memberships'), '1 superadmin + 3 org admins');

        $actions = $this->auditActions();
        self::assertContains('organization.created', $actions);
        self::assertSame(4, count(array_filter($actions, static fn (string $a): bool => $a === 'membership.granted')));
    }

    public function testIsIdempotentOnReRun(): void
    {
        $this->seedOperator('01J0OP100000000000000000ZA', '2026-01-01T00:00:00Z', 'first@example.com');
        $this->seedOperator('01J0OP200000000000000000ZA', '2026-02-01T00:00:00Z', 'second@example.com');

        $useCase = $this->useCase(self::ENV_EXTERNAL_ID);
        $useCase->execute(new BackfillTenancyInput());
        $useCase->execute(new BackfillTenancyInput());

        self::assertSame(1, $this->countRows('organizations'));
        self::assertSame(3, $this->countRows('memberships'), '1 superadmin + 2 org admins, no duplicates');
    }

    public function testReusesExistingOrganizationAndOnlyBackfillsMissing(): void
    {
        // An A4-installed host: org + first operator already fully provisioned.
        $org = new Organization(self::orgId(), self::ENV_EXTERNAL_ID, 'Acme KK', 'acme-kk', OrganizationStatus::Active, '2026-01-01T00:00:00Z', '2026-01-01T00:00:00Z');
        (new PdoOrganizationRepository($this->kit->queryExecutor))->save($org);
        $this->seedOperator('01J0OP100000000000000000ZA', '2026-01-01T00:00:00Z', 'first@example.com');
        $this->seedMembership('01J0MEMSUP00000000000000ZA', '01J0OP100000000000000000ZA', null, Role::Superadmin);
        $this->seedMembership('01J0MEMADM00000000000000ZA', '01J0OP100000000000000000ZA', self::orgId(), Role::Admin);
        // A new operator with no memberships.
        $this->seedOperator('01J0OP200000000000000000ZA', '2026-02-01T00:00:00Z', 'second@example.com');

        $output = $this->useCase(self::ENV_EXTERNAL_ID)->execute(new BackfillTenancyInput());

        self::assertSame(self::orgId(), $output->organization?->id, 'reuses the existing org, no second org');
        self::assertSame(1, $this->countRows('organizations'));

        $memberships = new PdoMembershipRepository($this->kit->queryExecutor);
        self::assertSame(Role::Admin, $memberships->findByOperatorAndOrganization('01J0OP200000000000000000ZA', self::orgId())?->role);
        self::assertNull($memberships->findByOperatorAndOrganization('01J0OP200000000000000000ZA', null), 'no second platform superadmin');
        self::assertSame(1, $this->countWhere('memberships', 'organization_id IS NULL'));
    }

    public function testReconcilesValidEnvExternalIdOntoTheCreatedOrg(): void
    {
        $this->seedOperator('01J0OP100000000000000000ZA', '2026-01-01T00:00:00Z', 'first@example.com');

        $output = $this->useCase(self::ENV_EXTERNAL_ID)->execute(new BackfillTenancyInput());

        self::assertSame(self::ENV_EXTERNAL_ID, $output->organization?->externalId, 'org external_id reconciles with the env value');
    }

    public function testMintsFreshExternalIdWhenEnvValueIsMalformed(): void
    {
        $malformed = '01J000000000000000000000000'; // 27 chars — not a valid ULID
        $this->seedOperator('01J0OP100000000000000000ZA', '2026-01-01T00:00:00Z', 'first@example.com');

        $output = $this->useCase($malformed)->execute(new BackfillTenancyInput());

        $externalId = $output->organization?->externalId;
        self::assertNotNull($externalId);
        self::assertNotSame($malformed, $externalId, 'a malformed env value is not used');
        self::assertTrue(Ulid::isValid($externalId), 'a fresh valid ULID is minted instead');
    }

    private static function orgId(): string
    {
        return '01J0ORG100000000000000000A';
    }

    private function useCase(string $orgExternalId): BackfillTenancyUseCase
    {
        $audit = new PdoSuiteAuditRecorderFactory(new SuiteAuditSanitizer());

        return new BackfillTenancyUseCase(
            new PdoOperatorRepository($this->kit->queryExecutor),
            new PdoOrganizationRepository($this->kit->queryExecutor),
            new PdoMembershipRepository($this->kit->queryExecutor),
            new CreateOrganizationUseCase($this->kit->transactionManager, new PdoOrganizationRepositoryFactory(), $audit, self::SUITE_ID),
            new GrantMembershipUseCase($this->kit->transactionManager, new PdoMembershipRepositoryFactory(), $audit, self::SUITE_ID),
            new PdoInstallSessionRepository($this->kit->queryExecutor),
            new PdoInstallManifestRepository($this->kit->queryExecutor),
            self::SUITE_ID,
            $orgExternalId,
        );
    }

    private function seedOperator(string $id, string $createdAt, string $email): void
    {
        (new PdoOperatorRepository($this->kit->queryExecutor))->save(new Operator(
            id: $id,
            email: $email,
            passwordHash: 'hash',
            displayName: null,
            createdAt: $createdAt,
            updatedAt: $createdAt,
        ));
    }

    private function seedMembership(string $id, string $operatorId, ?string $organizationId, Role $role): void
    {
        (new PdoMembershipRepository($this->kit->queryExecutor))->save(new Membership(
            id: $id,
            operatorId: $operatorId,
            organizationId: $organizationId,
            role: $role,
            createdAt: '2026-01-01T00:00:00Z',
            updatedAt: '2026-01-01T00:00:00Z',
        ));
    }

    /**
     * @return list<string>
     */
    private function auditActions(): array
    {
        $actions = [];

        foreach ($this->kit->queryExecutor->fetchAll('SELECT action FROM suite_audit_events') as $row) {
            $actions[] = (string) $row['action'];
        }

        return $actions;
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

    private function countWhere(string $table, string $where): int
    {
        $row = $this->kit->queryExecutor->fetchOne("SELECT COUNT(*) AS c FROM {$table} WHERE {$where}");

        return (int) ($row['c'] ?? 0);
    }
}

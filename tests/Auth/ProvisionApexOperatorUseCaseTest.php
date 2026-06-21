<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Testing\DatabaseTestKit;
use NeNeSuite\Auth\CreateOperatorInput;
use NeNeSuite\Auth\CreateOperatorUseCase;
use NeNeSuite\Auth\PasswordHasher;
use NeNeSuite\Auth\PdoOperatorRepositoryFactory;
use NeNeSuite\Auth\ProvisionApexOperatorUseCase;
use NeNeSuite\SuiteAudit\PdoSuiteAuditRecorderFactory;
use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use NeNeSuite\Tenancy\GrantMembershipUseCase;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\PdoMembershipRepository;
use NeNeSuite\Tenancy\PdoMembershipRepositoryFactory;
use NeNeSuite\Tenancy\PdoOrganizationRepository;
use NeNeSuite\Tenancy\Role;
use PHPUnit\Framework\TestCase;

/**
 * A4.5: a new operator created via the HTTP path must also receive a default-org admin
 * membership (so they are not zero-membership and locked out by A6). Exercised against a
 * real SQLite database because CreateOperator and GrantMembership are transactional.
 */
final class ProvisionApexOperatorUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const ORG_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';

    private string $dbPath;
    private DatabaseTestKit $kit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('nene-suite-prov-', true) . '.sqlite';
        $this->kit = DatabaseTestKit::sqlite($this->dbPath);

        foreach (['operators', 'organizations', 'memberships', 'suite_audit_events'] as $schema) {
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

    public function testCreatesOperatorAndGrantsDefaultOrgAdmin(): void
    {
        $this->seedDefaultOrganization();

        $output = $this->useCase(self::ORG_EXTERNAL_ID)
            ->execute(new CreateOperatorInput('ops@example.com', 'correcthorsebatterystaple'));

        $operator = $output->operator;
        self::assertSame('ops@example.com', $operator->email);

        $memberships = new PdoMembershipRepository($this->kit->queryExecutor);
        $orgMembership = $memberships->findByOperatorAndOrganization($operator->id, self::ORG_ID);
        self::assertNotNull($orgMembership);
        self::assertSame(Role::Admin, $orgMembership->role, 'new operator keeps pre-tenancy admin authority (ADR 0015 §8)');
        self::assertNull($memberships->findByOperatorAndOrganization($operator->id, null), 'no platform superadmin for additional operators');
    }

    public function testCreatesOperatorWithoutMembershipWhenDefaultOrgIsMissing(): void
    {
        $output = $this->useCase('01J0MISSINGORG00000000000Z')
            ->execute(new CreateOperatorInput('ops@example.com', 'correcthorsebatterystaple'));

        self::assertSame('ops@example.com', $output->operator->email);
        self::assertSame(1, $this->countRows('operators'), 'operator creation still succeeds');
        self::assertSame(0, $this->countRows('memberships'), 'no membership when the default org cannot be resolved');
    }

    private function seedDefaultOrganization(): void
    {
        (new PdoOrganizationRepository($this->kit->queryExecutor))->save(new Organization(
            id: self::ORG_ID,
            externalId: self::ORG_EXTERNAL_ID,
            name: 'Acme KK',
            slug: 'acme-kk',
            status: OrganizationStatus::Active,
            createdAt: '2026-06-21T00:00:00Z',
            updatedAt: '2026-06-21T00:00:00Z',
        ));
    }

    private function useCase(string $orgExternalId): ProvisionApexOperatorUseCase
    {
        $audit = new PdoSuiteAuditRecorderFactory(new SuiteAuditSanitizer());

        return new ProvisionApexOperatorUseCase(
            new CreateOperatorUseCase($this->kit->transactionManager, new PdoOperatorRepositoryFactory(), $audit, new PasswordHasher(), self::SUITE_ID),
            new PdoOrganizationRepository($this->kit->queryExecutor),
            new GrantMembershipUseCase($this->kit->transactionManager, new PdoMembershipRepositoryFactory(), $audit, self::SUITE_ID),
            new PdoMembershipRepository($this->kit->queryExecutor),
            $orgExternalId,
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

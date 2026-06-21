<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Testing\DatabaseTestKit;
use NeNeSuite\SuiteAudit\PdoSuiteAuditRecorderFactory;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use NeNeSuite\Tenancy\GrantMembershipInput;
use NeNeSuite\Tenancy\GrantMembershipUseCase;
use NeNeSuite\Tenancy\PdoMembershipRepositoryFactory;
use NeNeSuite\Tenancy\Role;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * End-to-end proof (against a real SQLite database) that the membership row and its
 * audit row commit or roll back together — ADR 0007 §5. File-backed because
 * `transactional()` opens a separate connection.
 */
final class GrantMembershipTransactionTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const OPERATOR_ID = '01J8XRDOP000000000000000ZA';

    private string $dbPath;
    private DatabaseTestKit $kit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('nene-suite-mem-', true) . '.sqlite';
        $this->kit = DatabaseTestKit::sqlite($this->dbPath);
        $this->loadSchema('memberships');
        $this->loadSchema('suite_audit_events');
    }

    protected function tearDown(): void
    {
        if (is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        parent::tearDown();
    }

    public function testCommitPersistsMembershipAndAuditTogether(): void
    {
        $this->useCase(new PdoSuiteAuditRecorderFactory(new SuiteAuditSanitizer()))
            ->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Superadmin));

        self::assertSame(1, $this->countRows('memberships'));
        self::assertSame(1, $this->countRows('suite_audit_events'));
    }

    public function testAuditFailureRollsBackTheMembershipInsert(): void
    {
        $useCase = $this->useCase($this->throwingAuditFactory());

        try {
            $useCase->execute(new GrantMembershipInput(self::OPERATOR_ID, Role::Superadmin));
            self::fail('Expected the audit failure to propagate.');
        } catch (RuntimeException $exception) {
            self::assertSame('audit write failed', $exception->getMessage());
        }

        self::assertSame(0, $this->countRows('memberships'));
        self::assertSame(0, $this->countRows('suite_audit_events'));
    }

    private function useCase(SuiteAuditRecorderFactoryInterface $audit): GrantMembershipUseCase
    {
        return new GrantMembershipUseCase(
            transactions: $this->kit->transactionManager,
            memberships: new PdoMembershipRepositoryFactory(),
            audit: $audit,
            suiteId: self::SUITE_ID,
        );
    }

    private function throwingAuditFactory(): SuiteAuditRecorderFactoryInterface
    {
        return new class () implements SuiteAuditRecorderFactoryInterface {
            public function create(DatabaseQueryExecutorInterface $query): SuiteAuditRecorderInterface
            {
                return new class () implements SuiteAuditRecorderInterface {
                    public function record(RecordSuiteAuditEventCommand $command): void
                    {
                        throw new RuntimeException('audit write failed');
                    }
                };
            }
        };
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

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Testing\DatabaseTestKit;
use NeNeSuite\Auth\CreateOperatorInput;
use NeNeSuite\Auth\CreateOperatorUseCase;
use NeNeSuite\Auth\PasswordHasher;
use NeNeSuite\Auth\PdoOperatorRepositoryFactory;
use NeNeSuite\SuiteAudit\PdoSuiteAuditRecorderFactory;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderInterface;
use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * End-to-end proof (against a real SQLite database) that the operator write and its
 * audit row commit or roll back together — ADR 0007 §5. Uses {@see DatabaseTestKit}
 * with a file-backed database because `transactional()` opens a separate connection.
 */
final class CreateOperatorTransactionTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';

    private string $dbPath;
    private DatabaseTestKit $kit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('nene-suite-op-', true) . '.sqlite';
        $this->kit = DatabaseTestKit::sqlite($this->dbPath);
        $this->loadSchema('operators');
        $this->loadSchema('suite_audit_events');
    }

    protected function tearDown(): void
    {
        if (is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        parent::tearDown();
    }

    public function testCommitPersistsOperatorAndAuditTogether(): void
    {
        $output = $this->useCase(new PdoSuiteAuditRecorderFactory(new SuiteAuditSanitizer()))
            ->execute(new CreateOperatorInput('admin@example.com', 'correcthorsebatterystaple', 'Admin'));

        self::assertSame(1, $this->countRows('operators'));
        self::assertSame(1, $this->countRows('suite_audit_events'));

        $operator = $this->kit->queryExecutor->fetchOne('SELECT email FROM operators WHERE id = ?', [$output->operator->id]);
        self::assertNotNull($operator);
        self::assertSame('admin@example.com', $operator['email']);
    }

    public function testAuditFailureRollsBackTheOperatorInsert(): void
    {
        $useCase = $this->useCase($this->throwingAuditFactory());

        try {
            $useCase->execute(new CreateOperatorInput('admin@example.com', 'correcthorsebatterystaple', 'Admin'));
            self::fail('Expected the audit failure to propagate.');
        } catch (RuntimeException $exception) {
            self::assertSame('audit write failed', $exception->getMessage());
        }

        // The operator row written before the failing audit call must be rolled back.
        self::assertSame(0, $this->countRows('operators'));
        self::assertSame(0, $this->countRows('suite_audit_events'));
    }

    private function useCase(SuiteAuditRecorderFactoryInterface $audit): CreateOperatorUseCase
    {
        return new CreateOperatorUseCase(
            transactions: $this->kit->transactionManager,
            operators: new PdoOperatorRepositoryFactory(),
            audit: $audit,
            hasher: new PasswordHasher(),
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

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
use NeNeSuite\Tenancy\CreateOrganizationInput;
use NeNeSuite\Tenancy\CreateOrganizationUseCase;
use NeNeSuite\Tenancy\PdoOrganizationRepositoryFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * End-to-end proof (against a real SQLite database) that the organization row and its
 * audit row commit or roll back together — ADR 0007 §5. File-backed because
 * `transactional()` opens a separate connection.
 */
final class CreateOrganizationTransactionTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';

    private string $dbPath;
    private DatabaseTestKit $kit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = sys_get_temp_dir() . '/' . uniqid('nene-suite-org-', true) . '.sqlite';
        $this->kit = DatabaseTestKit::sqlite($this->dbPath);
        $this->loadSchema('organizations');
        $this->loadSchema('suite_audit_events');
    }

    protected function tearDown(): void
    {
        if (is_file($this->dbPath)) {
            unlink($this->dbPath);
        }

        parent::tearDown();
    }

    public function testCommitPersistsOrganizationAndAuditTogether(): void
    {
        $output = $this->useCase(new PdoSuiteAuditRecorderFactory(new SuiteAuditSanitizer()))
            ->execute(new CreateOrganizationInput('Acme KK', 'acme-kk'));

        self::assertSame(1, $this->countRows('organizations'));
        self::assertSame(1, $this->countRows('suite_audit_events'));

        $row = $this->kit->queryExecutor->fetchOne('SELECT slug, status FROM organizations WHERE id = ?', [$output->organization->id]);
        self::assertNotNull($row);
        self::assertSame('acme-kk', $row['slug']);
        self::assertSame('active', $row['status']);
    }

    public function testAuditFailureRollsBackTheOrganizationInsert(): void
    {
        $useCase = $this->useCase($this->throwingAuditFactory());

        try {
            $useCase->execute(new CreateOrganizationInput('Acme KK', 'acme-kk'));
            self::fail('Expected the audit failure to propagate.');
        } catch (RuntimeException $exception) {
            self::assertSame('audit write failed', $exception->getMessage());
        }

        self::assertSame(0, $this->countRows('organizations'));
        self::assertSame(0, $this->countRows('suite_audit_events'));
    }

    private function useCase(SuiteAuditRecorderFactoryInterface $audit): CreateOrganizationUseCase
    {
        return new CreateOrganizationUseCase(
            transactions: $this->kit->transactionManager,
            organizations: new PdoOrganizationRepositoryFactory(),
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

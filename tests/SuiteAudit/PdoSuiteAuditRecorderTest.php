<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SuiteAudit;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\SuiteAudit\PdoSuiteAuditRecorder;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditSanitizer;
use PHPUnit\Framework\TestCase;

final class PdoSuiteAuditRecorderTest extends TestCase
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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/suite_audit_events.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testRecordsSanitizedRow(): void
    {
        $recorder = new PdoSuiteAuditRecorder($this->executor, new SuiteAuditSanitizer());

        $recorder->record(new RecordSuiteAuditEventCommand(
            suiteId: '01J8XRDEV000000000000000ZA',
            action: 'env_config.written',
            entityType: 'suite_env_config',
            entityId: '01J8XRDEV000000000000000ZA',
            beforeJson: null,
            afterJson: [
                'DB_NAME' => 'nene_suite',
                'DB_PASSWORD' => 'hunter2',
            ],
            source: 'installer_ui',
            installSessionId: '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
        ));

        $rows = $this->executor->fetchAll('SELECT * FROM suite_audit_events');
        self::assertCount(1, $rows);

        $row = $rows[0];
        self::assertSame('env_config.written', $row['action']);
        self::assertSame('suite_env_config', $row['entity_type']);
        self::assertNull($row['before_json']);
        self::assertSame('01J8XR4ZS6Q9V2H7K3N5M0B8TC', $row['install_session_id']);

        $after = json_decode((string) $row['after_json'], true);
        self::assertIsArray($after);
        self::assertSame('nene_suite', $after['DB_NAME']);
        self::assertSame(SuiteAuditSanitizer::REDACTED, $after['DB_PASSWORD']);

        self::assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', (string) $row['id']);
    }
}

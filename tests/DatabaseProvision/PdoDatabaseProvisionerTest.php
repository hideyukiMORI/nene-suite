<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\DatabaseProvision;

use NeNeSuite\DatabaseProvision\PdoDatabaseProvisioner;
use PHPUnit\Framework\TestCase;

/**
 * Unit-tests the per-engine CREATE DATABASE DDL generation (ADR 0016). The execution
 * path is engine-specific and verified manually against MySQL/PostgreSQL; here we pin
 * the generated SQL so the MySQL form and the PostgreSQL form stay correct and distinct.
 */
final class PdoDatabaseProvisionerTest extends TestCase
{
    public function testMysqlCreateSqlUsesIfNotExistsAndUtf8mb4(): void
    {
        self::assertSame(
            'CREATE DATABASE IF NOT EXISTS `nene_invoice` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
            PdoDatabaseProvisioner::mysqlCreateSql('nene_invoice'),
        );
    }

    public function testPostgresCreateSqlQuotesIdentifierAndSetsEncoding(): void
    {
        self::assertSame(
            'CREATE DATABASE "nene_invoice" ENCODING \'UTF8\' TEMPLATE template0',
            PdoDatabaseProvisioner::postgresCreateSql('nene_invoice'),
        );
    }

    public function testPostgresDdlOmitsMysqlOnlyClauses(): void
    {
        $sql = PdoDatabaseProvisioner::postgresCreateSql('nene_clear');

        self::assertStringNotContainsString('IF NOT EXISTS', $sql);
        self::assertStringNotContainsString('`', $sql);
        self::assertStringNotContainsString('CHARACTER SET', $sql);
    }
}

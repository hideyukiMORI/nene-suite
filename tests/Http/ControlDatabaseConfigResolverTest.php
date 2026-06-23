<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Http;

use Nene2\Config\ConfigLoader;
use NeNeSuite\Http\ControlDatabaseConfigResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ControlDatabaseConfigResolverTest extends TestCase
{
    private const ENV_VAR = 'NENE_SUITE_CONTROL_DATABASE_URL';

    protected function tearDown(): void
    {
        unset($_SERVER[self::ENV_VAR], $_ENV[self::ENV_VAR]);

        parent::tearDown();
    }

    public function testParsesFullUrl(): void
    {
        $_SERVER[self::ENV_VAR] = 'mysql://suite_user:s3cret@db:3306/nene_suite';

        $config = (new ControlDatabaseConfigResolver())->resolve($this->fallback());

        self::assertNull($config->url);
        self::assertSame('mysql', $config->adapter);
        self::assertSame('db', $config->host);
        self::assertSame(3306, $config->port);
        self::assertSame('nene_suite', $config->name);
        self::assertSame('suite_user', $config->user);
        self::assertSame('s3cret', $config->password);
        self::assertSame('production', $config->environment);
    }

    public function testDefaultsPortTo3306WhenOmitted(): void
    {
        $_SERVER[self::ENV_VAR] = 'mysql://u:p@localhost/mydb';

        $config = (new ControlDatabaseConfigResolver())->resolve($this->fallback());

        self::assertSame(3306, $config->port);
        self::assertSame('mydb', $config->name);
    }

    public function testParsesPgsqlUrl(): void
    {
        $_SERVER[self::ENV_VAR] = 'pgsql://suite_user:s3cret@db:5432/nene_suite';

        $config = (new ControlDatabaseConfigResolver())->resolve($this->fallback());

        self::assertSame('pgsql', $config->adapter);
        self::assertSame('db', $config->host);
        self::assertSame(5432, $config->port);
        self::assertSame('nene_suite', $config->name);
        self::assertSame('utf8', $config->charset);
    }

    public function testDefaultsPgsqlPortTo5432WhenOmitted(): void
    {
        $_SERVER[self::ENV_VAR] = 'pgsql://u:p@localhost/mydb';

        $config = (new ControlDatabaseConfigResolver())->resolve($this->fallback());

        self::assertSame(5432, $config->port);
    }

    #[DataProvider('postgresSchemeProvider')]
    public function testNormalizesPostgresSchemesToPgsql(string $scheme): void
    {
        $_SERVER[self::ENV_VAR] = "{$scheme}://u:p@db/mydb";

        $config = (new ControlDatabaseConfigResolver())->resolve($this->fallback());

        self::assertSame('pgsql', $config->adapter);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function postgresSchemeProvider(): array
    {
        return [
            'pgsql' => ['pgsql'],
            'postgres' => ['postgres'],
            'postgresql' => ['postgresql'],
        ];
    }

    public function testThrowsOnUnsupportedScheme(): void
    {
        $_SERVER[self::ENV_VAR] = 'sqlsrv://u:p@db/mydb';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/unsupported scheme/');

        (new ControlDatabaseConfigResolver())->resolve($this->fallback());
    }

    public function testControlAdapterDerivesEngineFromUrlScheme(): void
    {
        $_SERVER[self::ENV_VAR] = 'pgsql://u:p@db:5432/nene_suite';
        self::assertSame('pgsql', ControlDatabaseConfigResolver::controlAdapter());

        $_SERVER[self::ENV_VAR] = 'mysql://u:p@db:3306/nene_suite';
        self::assertSame('mysql', ControlDatabaseConfigResolver::controlAdapter());
    }

    public function testControlAdapterReturnsNullWhenUnsetOrUnsupported(): void
    {
        self::assertNull(ControlDatabaseConfigResolver::controlAdapter());

        $_SERVER[self::ENV_VAR] = 'sqlsrv://u:p@db/mydb';
        self::assertNull(ControlDatabaseConfigResolver::controlAdapter());
    }

    public function testFallsBackToConfigLoaderWhenUnset(): void
    {
        $fallback = new ConfigLoader(__DIR__ . '/../..', [
            'APP_ENV' => 'local',
            'APP_DEBUG' => 'false',
            'APP_NAME' => 'test',
            'NENE2_MACHINE_API_KEY' => '',
            'NENE2_LOCAL_JWT_SECRET' => '',
            'PROBLEM_DETAILS_BASE_URL' => 'https://nene-suite.dev/problems/',
            'DATABASE_URL' => '',
            'DB_ENV' => 'testing',
            'DB_ADAPTER' => 'sqlite',
            'DB_HOST' => 'localhost',
            'DB_PORT' => '1',
            'DB_NAME' => ':memory:',
            'DB_USER' => 'sqlite',
            'DB_PASSWORD' => '',
            'DB_CHARSET' => 'utf8',
        ]);

        $config = (new ControlDatabaseConfigResolver())->resolve($fallback);

        self::assertSame('sqlite', $config->adapter);
        self::assertSame(':memory:', $config->name);
        self::assertSame('testing', $config->environment);
    }

    public function testRawUrlReturnsNullWhenUnset(): void
    {
        self::assertNull(ControlDatabaseConfigResolver::rawUrl());
    }

    public function testRawUrlReturnsValueWhenSet(): void
    {
        $_SERVER[self::ENV_VAR] = 'mysql://u:p@host/db';

        self::assertSame('mysql://u:p@host/db', ControlDatabaseConfigResolver::rawUrl());
    }

    public function testThrowsOnMissingDbNameInPath(): void
    {
        $_SERVER[self::ENV_VAR] = 'mysql://u:p@host';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/database name path/');

        (new ControlDatabaseConfigResolver())->resolve($this->fallback());
    }

    private function fallback(): ConfigLoader
    {
        return new ConfigLoader(__DIR__ . '/../../..');
    }
}

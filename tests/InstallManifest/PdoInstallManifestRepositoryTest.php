<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\InstallManifest;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\InstallManifest\InstallManifestFactory;
use NeNeSuite\InstallManifest\PdoInstallManifestRepository;
use PHPUnit\Framework\TestCase;

final class PdoInstallManifestRepositoryTest extends TestCase
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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/install_manifests.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveAndFindByIdRoundTrip(): void
    {
        $repository = new PdoInstallManifestRepository($this->executor);

        $manifest = (new InstallManifestFactory())->create(
            '01J8XRDEV000000000000000ZA',
            '01J8XRDEV0FED0000000000ZAB',
            'Example Organization',
            '2026-05-30T09:50:00Z',
        );
        $repository->save($manifest);

        $found = $repository->findById($manifest->id);

        self::assertNotNull($found);
        self::assertSame($manifest->id, $found->id);
        self::assertSame($manifest->contentHash, $found->contentHash);
        self::assertSame('01J8XRDEV000000000000000ZA', $found->body['suite_id']);
        self::assertSame('01J8XRDEV0FED0000000000ZAB', $found->body['org_external_id']);
        self::assertSame([], $found->body['enabled_integrations']);
    }
}

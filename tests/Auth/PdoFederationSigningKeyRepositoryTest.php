<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Auth\FederationSigningKey;
use NeNeSuite\Auth\FederationSigningKeyStatus;
use NeNeSuite\Auth\PdoFederationSigningKeyRepository;
use PHPUnit\Framework\TestCase;

final class PdoFederationSigningKeyRepositoryTest extends TestCase
{
    private const NOW = '2026-06-22T00:00:00Z';

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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/federation_signing_keys.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveAndFindActive(): void
    {
        $repository = new PdoFederationSigningKeyRepository($this->executor);
        self::assertNull($repository->findActive());

        $repository->save(new FederationSigningKey(
            '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            'kid-1',
            'ES256',
            '{"kty":"EC","kid":"kid-1"}',
            FederationSigningKeyStatus::Active,
            self::NOW,
            self::NOW,
            null,
        ));

        $active = $repository->findActive();
        self::assertNotNull($active);
        self::assertSame('kid-1', $active->kid);
        self::assertSame('ES256', $active->alg);
        self::assertSame('{"kty":"EC","kid":"kid-1"}', $active->publicJwk);
        self::assertSame(FederationSigningKeyStatus::Active, $active->status);
        self::assertNull($active->retiredAt);
    }

    public function testFindActiveIgnoresNonActiveRows(): void
    {
        $repository = new PdoFederationSigningKeyRepository($this->executor);
        $repository->save(new FederationSigningKey(
            '01J8XR4ZS6Q9V2H7K3N5M0B8TD',
            'kid-retiring',
            'ES256',
            '{}',
            FederationSigningKeyStatus::Retiring,
            self::NOW,
            self::NOW,
            null,
        ));

        self::assertNull($repository->findActive());
    }
}

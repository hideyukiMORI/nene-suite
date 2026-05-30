<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\PdoOperatorRepository;
use PHPUnit\Framework\TestCase;

final class PdoOperatorRepositoryTest extends TestCase
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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/operators.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveAndLookupByIdAndEmail(): void
    {
        $repository = new PdoOperatorRepository($this->executor);
        $operator = new Operator(
            id: '01J8XR0G7Q9V2H7K3N5M0B8TCA',
            email: 'operator@example.com',
            passwordHash: '$2y$10$abcdefghijklmnopqrstuv',
            displayName: 'Example Operator',
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
        );
        $repository->save($operator);

        $byId = $repository->findById('01J8XR0G7Q9V2H7K3N5M0B8TCA');
        $byEmail = $repository->findByEmail('operator@example.com');

        self::assertNotNull($byId);
        self::assertNotNull($byEmail);
        self::assertSame('operator@example.com', $byId->email);
        self::assertSame('01J8XR0G7Q9V2H7K3N5M0B8TCA', $byEmail->id);
        self::assertSame('Example Operator', $byEmail->displayName);
    }

    public function testReturnsNullForUnknownLookups(): void
    {
        $repository = new PdoOperatorRepository($this->executor);

        self::assertNull($repository->findById('01J8XR0G7Q9V2H7K3N5M0B8TCA'));
        self::assertNull($repository->findByEmail('nobody@example.com'));
    }
}

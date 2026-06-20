<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\PdoOrganizationRepository;
use PHPUnit\Framework\TestCase;

final class PdoOrganizationRepositoryTest extends TestCase
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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/organizations.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveAndLookupByIdExternalIdAndSlug(): void
    {
        $repository = new PdoOrganizationRepository($this->executor);
        $organization = $this->organization();
        $repository->save($organization);

        $byId = $repository->findById($organization->id);
        $byExternalId = $repository->findByExternalId('01J0YYYYYYYYYYYYYYYYYYYYYY');
        $bySlug = $repository->findBySlug('example-kk');

        self::assertNotNull($byId);
        self::assertNotNull($byExternalId);
        self::assertNotNull($bySlug);
        self::assertSame($organization->id, $byExternalId->id);
        self::assertSame('Example KK', $bySlug->name);
        self::assertSame(OrganizationStatus::Active, $byId->status);
    }

    public function testLookupMissesReturnNull(): void
    {
        $repository = new PdoOrganizationRepository($this->executor);

        self::assertNull($repository->findById('01J0000000000000000000MISS'));
        self::assertNull($repository->findByExternalId('01J0000000000000000000MISS'));
        self::assertNull($repository->findBySlug('missing'));
    }

    public function testAllReturnsSavedOrganizations(): void
    {
        $repository = new PdoOrganizationRepository($this->executor);
        self::assertSame([], $repository->all());

        $repository->save($this->organization());

        $all = $repository->all();
        self::assertCount(1, $all);
        self::assertSame('example-kk', $all[0]->slug);
    }

    public function testDisabledStatusRoundTrips(): void
    {
        $repository = new PdoOrganizationRepository($this->executor);
        $repository->save($this->organization(status: OrganizationStatus::Disabled));

        $found = $repository->findBySlug('example-kk');

        self::assertNotNull($found);
        self::assertSame(OrganizationStatus::Disabled, $found->status);
    }

    private function organization(OrganizationStatus $status = OrganizationStatus::Active): Organization
    {
        return new Organization(
            id: '01J0XR0G7Q9V2H7K3N5M0B8ORG',
            externalId: '01J0YYYYYYYYYYYYYYYYYYYYYY',
            name: 'Example KK',
            slug: 'example-kk',
            status: $status,
            createdAt: '2026-06-21T00:00:00Z',
            updatedAt: '2026-06-21T00:00:00Z',
        );
    }
}

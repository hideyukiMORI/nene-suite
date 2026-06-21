<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Config\DatabaseConfig;
use Nene2\Database\PdoConnectionFactory;
use Nene2\Database\PdoDatabaseQueryExecutor;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\PdoMembershipRepository;
use NeNeSuite\Tenancy\Role;
use PHPUnit\Framework\TestCase;

final class PdoMembershipRepositoryTest extends TestCase
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

        $schema = (string) file_get_contents(dirname(__DIR__, 2) . '/database/schema/memberships.sql');
        foreach (preg_split('/;\R/s', trim($schema)) ?: [] as $chunk) {
            $statement = trim($chunk);
            if ($statement !== '') {
                $this->executor->execute($statement);
            }
        }
    }

    public function testSaveAndLookupScopedMembership(): void
    {
        $repository = new PdoMembershipRepository($this->executor);
        $membership = $this->membership(
            id: '01J0MEM0000000000000000ADM',
            organizationId: '01J0ORG000000000000000000A',
            role: Role::Admin,
        );
        $repository->save($membership);

        $byId = $repository->findById('01J0MEM0000000000000000ADM');
        $byPair = $repository->findByOperatorAndOrganization('01J0OP0000000000000000000A', '01J0ORG000000000000000000A');

        self::assertNotNull($byId);
        self::assertNotNull($byPair);
        self::assertSame(Role::Admin, $byPair->role);
        self::assertSame('01J0ORG000000000000000000A', $byPair->organizationId);
    }

    public function testPlatformMembershipHasNullOrganization(): void
    {
        $repository = new PdoMembershipRepository($this->executor);
        $repository->save($this->membership(
            id: '01J0MEM00000000000000SUPER',
            organizationId: null,
            role: Role::Superadmin,
        ));

        $platform = $repository->findByOperatorAndOrganization('01J0OP0000000000000000000A', null);

        self::assertNotNull($platform);
        self::assertNull($platform->organizationId);
        self::assertSame(Role::Superadmin, $platform->role);
        self::assertTrue($platform->role->isPlatform());
    }

    public function testFindByOperatorReturnsAllMemberships(): void
    {
        $repository = new PdoMembershipRepository($this->executor);
        $repository->save($this->membership('01J0MEM00000000000000SUPER', null, Role::Superadmin));
        $repository->save($this->membership('01J0MEM0000000000000000ADM', '01J0ORG000000000000000000A', Role::Admin));

        $all = $repository->findByOperator('01J0OP0000000000000000000A');

        self::assertCount(2, $all);
    }

    public function testFindByOrganizationScopesToOrg(): void
    {
        $repository = new PdoMembershipRepository($this->executor);
        $repository->save($this->membership('01J0MEM0000000000000000ADM', '01J0ORG000000000000000000A', Role::Admin));
        $repository->save($this->membership(
            id: '01J0MEM0000000000000000MEM',
            operatorId: '01J0OP0000000000000000000B',
            organizationId: '01J0ORG000000000000000000A',
            role: Role::Member,
        ));

        $members = $repository->findByOrganization('01J0ORG000000000000000000A');

        self::assertCount(2, $members);
    }

    public function testUnknownLookupsReturnNull(): void
    {
        $repository = new PdoMembershipRepository($this->executor);

        self::assertNull($repository->findById('01J0MEM0000000000000000MIS'));
        self::assertNull($repository->findByOperatorAndOrganization('01J0OP0000000000000000000A', null));
        self::assertSame([], $repository->findByOperator('01J0OP0000000000000000000A'));
    }

    public function testUpdateRolePersistsAndPreservesImmutableFields(): void
    {
        $repository = new PdoMembershipRepository($this->executor);
        $repository->save($this->membership('01J0MEM0000000000000000ADM', '01J0ORG000000000000000000A', Role::Admin));

        $repository->updateRole(new Membership(
            id: '01J0MEM0000000000000000ADM',
            operatorId: '01J0OP0000000000000000000A',
            organizationId: '01J0ORG000000000000000000A',
            role: Role::Member,
            createdAt: '2026-06-21T00:00:00Z',
            updatedAt: '2026-06-22T09:00:00Z',
        ));

        $found = $repository->findById('01J0MEM0000000000000000ADM');

        self::assertNotNull($found);
        self::assertSame(Role::Member, $found->role);
        self::assertSame('2026-06-22T09:00:00Z', $found->updatedAt);
        // operator_id, organization_id and created_at are immutable.
        self::assertSame('01J0OP0000000000000000000A', $found->operatorId);
        self::assertSame('01J0ORG000000000000000000A', $found->organizationId);
        self::assertSame('2026-06-21T00:00:00Z', $found->createdAt);
    }

    public function testDeleteRemovesMembership(): void
    {
        $repository = new PdoMembershipRepository($this->executor);
        $repository->save($this->membership('01J0MEM0000000000000000ADM', '01J0ORG000000000000000000A', Role::Admin));

        $repository->delete('01J0MEM0000000000000000ADM');

        self::assertNull($repository->findById('01J0MEM0000000000000000ADM'));
    }

    public function testFindPlatformMembershipsReturnsOnlyNullOrganization(): void
    {
        $repository = new PdoMembershipRepository($this->executor);
        $repository->save($this->membership('01J0MEM00000000000000SUPER', null, Role::Superadmin));
        $repository->save($this->membership(
            id: '01J0MEM0000000000000000ADM',
            operatorId: '01J0OP0000000000000000000B',
            organizationId: '01J0ORG000000000000000000A',
            role: Role::Admin,
        ));

        $platform = $repository->findPlatformMemberships();

        self::assertCount(1, $platform);
        self::assertSame('01J0MEM00000000000000SUPER', $platform[0]->id);
        self::assertNull($platform[0]->organizationId);
    }

    private function membership(
        string $id,
        ?string $organizationId,
        Role $role,
        string $operatorId = '01J0OP0000000000000000000A',
    ): Membership {
        return new Membership(
            id: $id,
            operatorId: $operatorId,
            organizationId: $organizationId,
            role: $role,
            createdAt: '2026-06-21T00:00:00Z',
            updatedAt: '2026-06-21T00:00:00Z',
        );
    }
}

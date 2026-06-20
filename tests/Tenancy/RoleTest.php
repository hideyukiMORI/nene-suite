<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\Role;
use PHPUnit\Framework\TestCase;

final class RoleTest extends TestCase
{
    public function testSuperadminIsPlatformAndNeedsNoOrganization(): void
    {
        self::assertTrue(Role::Superadmin->isPlatform());
        self::assertFalse(Role::Superadmin->requiresOrganization());
    }

    public function testScopedRolesRequireAnOrganization(): void
    {
        foreach ([Role::Admin, Role::Member, Role::Viewer] as $role) {
            self::assertFalse($role->isPlatform());
            self::assertTrue($role->requiresOrganization());
        }
    }

    public function testValuesAreStable(): void
    {
        self::assertSame('superadmin', Role::Superadmin->value);
        self::assertSame('admin', Role::Admin->value);
        self::assertSame('member', Role::Member->value);
        self::assertSame('viewer', Role::Viewer->value);
    }
}

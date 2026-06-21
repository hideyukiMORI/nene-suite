<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Installer;

use NeNeSuite\Installer\OrgSlugDeriver;
use PHPUnit\Framework\TestCase;

final class OrgSlugDeriverTest extends TestCase
{
    private const SEED = '01J8XRDEV000000000000000ZA';

    public function testDerivesSlugFromAsciiName(): void
    {
        self::assertSame('acme-kk', OrgSlugDeriver::derive('Acme KK', self::SEED));
    }

    public function testCollapsesAndTrimsNonAlphanumericRuns(): void
    {
        self::assertSame('acme-k-k', OrgSlugDeriver::derive('  Acme,  K.K.!  ', self::SEED));
    }

    public function testLowercasesAndKeepsDigits(): void
    {
        self::assertSame('studio-42', OrgSlugDeriver::derive('Studio 42', self::SEED));
    }

    public function testFallsBackToDeterministicSlugForNonAsciiName(): void
    {
        $slug = OrgSlugDeriver::derive('ねね商店', self::SEED);

        self::assertMatchesRegularExpression('/^org-[0-9a-f]{12}$/', $slug);
        self::assertSame($slug, OrgSlugDeriver::derive('ねね商店', self::SEED), 'fallback is deterministic for a given seed');
    }

    public function testFallbackVariesBySeed(): void
    {
        self::assertNotSame(
            OrgSlugDeriver::derive('日本語', 'seed-a'),
            OrgSlugDeriver::derive('日本語', 'seed-b'),
        );
    }

    public function testCapsLengthAtSlugMaximum(): void
    {
        self::assertSame(160, mb_strlen(OrgSlugDeriver::derive(str_repeat('a', 500), self::SEED)));
    }

    public function testAlwaysProducesAValidSlug(): void
    {
        $names = ['Acme KK', 'ねね商店', '   ', '---', 'A', '42', 'Café Münchën', str_repeat('x', 300)];

        foreach ($names as $name) {
            self::assertMatchesRegularExpression(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                OrgSlugDeriver::derive($name, self::SEED),
                "slug for '{$name}' must satisfy the CreateOrganization slug rule",
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Http;

use Nene2\Auth\JwtSecretException;
use Nene2\Config\AppEnvironment;
use NeNeSuite\Http\JwtSecretResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JwtSecretResolverTest extends TestCase
{
    public function testReturnsConfiguredSecretWhenSet(): void
    {
        $resolver = new JwtSecretResolver('a-real-random-secret', '', AppEnvironment::Local);

        self::assertSame('a-real-random-secret', $resolver->resolve());
    }

    public function testConfiguredSecretWinsEvenWhenDevOptInIsSet(): void
    {
        $resolver = new JwtSecretResolver('a-real-random-secret', '1', AppEnvironment::Local);

        self::assertSame('a-real-random-secret', $resolver->resolve());
    }

    public function testConfiguredSecretIsUsedInProduction(): void
    {
        $resolver = new JwtSecretResolver('a-real-random-secret', '', AppEnvironment::Production);

        self::assertSame('a-real-random-secret', $resolver->resolve());
    }

    /**
     * @return list<array{string}>
     */
    public static function devOptInValues(): array
    {
        return [['1'], ['true'], ['yes'], ['YES'], ['  true  ']];
    }

    #[DataProvider('devOptInValues')]
    public function testReturnsDevSecretWhenUnsetAndOptInEnabled(string $optIn): void
    {
        $resolver = new JwtSecretResolver('', $optIn, AppEnvironment::Local);

        self::assertSame(JwtSecretResolver::DEV_JWT_SECRET, $resolver->resolve());
    }

    public function testThrowsWhenUnsetAndNoOptIn(): void
    {
        $resolver = new JwtSecretResolver('', '', AppEnvironment::Local);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/NENE_SUITE_JWT_SECRET.*NENE_SUITE_ALLOW_DEV_SECRET/s');

        $resolver->resolve();
    }

    /**
     * @return list<array{string}>
     */
    public static function nonTruthyOptInValues(): array
    {
        return [['0'], ['false'], ['no'], ['off'], ['2'], ['maybe'], ['']];
    }

    #[DataProvider('nonTruthyOptInValues')]
    public function testThrowsWhenOptInIsNotStrictlyTruthy(string $optIn): void
    {
        $resolver = new JwtSecretResolver('', $optIn, AppEnvironment::Local);

        $this->expectException(RuntimeException::class);

        $resolver->resolve();
    }

    public function testProductionIgnoresDevOptInAndThrows(): void
    {
        $resolver = new JwtSecretResolver('', '1', AppEnvironment::Production);

        $this->expectException(JwtSecretException::class);
        $this->expectExceptionMessageMatches('/intentionally ignored in production/');

        $resolver->resolve();
    }

    public function testProductionThrowsWhenUnset(): void
    {
        $resolver = new JwtSecretResolver('', '', AppEnvironment::Production);

        $this->expectException(JwtSecretException::class);
        $this->expectExceptionMessageMatches('/NENE_SUITE_JWT_SECRET/');

        $resolver->resolve();
    }
}

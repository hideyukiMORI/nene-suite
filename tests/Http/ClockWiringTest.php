<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Http;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\ClockInterface;
use Nene2\Http\UtcClock;
use NeNeSuite\AppCatalog\ListCatalogAppsHandler;
use NeNeSuite\Auth\CreateAuthSessionUseCase;
use NeNeSuite\Auth\CreateAuthSessionUseCaseInterface;
use NeNeSuite\Auth\LoginRateLimiter;
use NeNeSuite\Auth\PdoRevokedTokenRepository;
use NeNeSuite\Auth\RevokedTokenRepositoryInterface;
use NeNeSuite\Auth\SwitchActiveOrganizationUseCase;
use NeNeSuite\Auth\SwitchActiveOrganizationUseCaseInterface;
use NeNeSuite\Deploy\GetDeployPlanHandler;
use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Origin\GetOriginFeedsHandler;
use NeNeSuite\Origin\GetOriginUpdatesHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

/**
 * Standing gate for the single-time-source invariant: every service that needs "now" must receive
 * the container's {@see ClockInterface}, not read the wall clock itself.
 *
 * This is what makes the auth path testable end to end — a token stamped by
 * {@see CreateAuthSessionUseCase} and checked by {@see LocalBearerTokenVerifier} closes over one
 * instant, so `exp` boundaries are exact rather than racing two independent reads. A future service
 * that quietly falls back to `time()` or `new DateTimeImmutable('now')` turns this red.
 */
final class ClockWiringTest extends TestCase
{
    public function testClockIsBoundToUtcClock(): void
    {
        $clock = $this->container()->get(ClockInterface::class);

        self::assertInstanceOf(UtcClock::class, $clock);
    }

    public function testUtcClockMatchesTheTimeSourceItReplaced(): void
    {
        // The adoption is behavior-preserving only if this holds: UtcClock reads the same Unix
        // second `time()` did (UTC, DST-independent).
        $before = time();
        $viaClock = (new UtcClock())->now()->getTimestamp();
        $after = time();

        self::assertGreaterThanOrEqual($before, $viaClock);
        self::assertLessThanOrEqual($after, $viaClock);
    }

    /**
     * @return iterable<string, array{class-string, string, string}>
     */
    public static function clockConsumers(): iterable
    {
        // service id => [concrete class, property holding the clock, ...]
        yield 'apex token verifier' => [LocalBearerTokenVerifier::class, LocalBearerTokenVerifier::class, 'clock'];
        yield 'login rate limiter' => [LoginRateLimiter::class, LoginRateLimiter::class, 'clock'];
        yield 'revoked token repository' => [RevokedTokenRepositoryInterface::class, PdoRevokedTokenRepository::class, 'clock'];
        yield 'create auth session' => [CreateAuthSessionUseCaseInterface::class, CreateAuthSessionUseCase::class, 'clock'];
        yield 'switch active organization' => [SwitchActiveOrganizationUseCaseInterface::class, SwitchActiveOrganizationUseCase::class, 'clock'];
        yield 'catalog apps handler' => [ListCatalogAppsHandler::class, ListCatalogAppsHandler::class, 'clock'];
        yield 'deploy plan handler' => [GetDeployPlanHandler::class, GetDeployPlanHandler::class, 'clock'];
        yield 'origin updates handler' => [GetOriginUpdatesHandler::class, GetOriginUpdatesHandler::class, 'clock'];
        yield 'origin feeds handler' => [GetOriginFeedsHandler::class, GetOriginFeedsHandler::class, 'clock'];
    }

    /**
     * @param class-string $serviceId
     * @param class-string $concrete
     */
    #[DataProvider('clockConsumers')]
    public function testConsumerReceivesTheContainerClock(string $serviceId, string $concrete, string $property): void
    {
        $container = $this->container();
        $clock = $container->get(ClockInterface::class);
        $service = $container->get($serviceId);

        self::assertInstanceOf($concrete, $service);
        self::assertSame($clock, (new ReflectionProperty($concrete, $property))->getValue($service));
    }

    private function container(): ContainerInterface
    {
        return (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();
    }
}

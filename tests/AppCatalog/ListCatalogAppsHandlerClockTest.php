<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use DateTimeImmutable;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\AppCatalog\ListCatalogAppsHandler;
use NeNeSuite\AppCatalog\ListCatalogAppsOutput;
use NeNeSuite\AppCatalog\ListCatalogAppsUseCaseInterface;
use NeNeSuite\Tests\Support\FixedClock;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

/**
 * The handler must hand the use case the injected clock's instant. It is the representative case
 * for the four read handlers that previously constructed `new DateTimeImmutable('now')` inline —
 * {@see \NeNeSuite\Tests\Http\ClockWiringTest} covers that all four receive the container clock.
 */
final class ListCatalogAppsHandlerClockTest extends TestCase
{
    public function testPassesTheInjectedInstantToTheUseCase(): void
    {
        $useCase = new class () implements ListCatalogAppsUseCaseInterface {
            public ?DateTimeImmutable $seenNow = null;

            public function execute(DateTimeImmutable $now): ListCatalogAppsOutput
            {
                $this->seenNow = $now;

                return new ListCatalogAppsOutput(1, []);
            }
        };

        $clock = new FixedClock('2026-08-04T12:34:56Z');
        $psr17 = new Psr17Factory();
        $handler = new ListCatalogAppsHandler($useCase, new JsonResponseFactory($psr17, $psr17), $clock);

        $response = $handler->handle($psr17->createServerRequest('GET', '/api/v1/catalog/apps'));

        self::assertSame(200, $response->getStatusCode());
        self::assertNotNull($useCase->seenNow);
        self::assertSame('2026-08-04T12:34:56+00:00', $useCase->seenNow->format('c'));
    }
}

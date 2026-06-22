<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Http;

use Nene2\Routing\Router;
use NeNeSuite\ApplicationServiceProvider;
use NeNeSuite\Http\RuntimeContainerFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Counterpart to {@see OssEditionFirewallTest}: with NENE_SUITE_EDITION=hosted the federation JWKS
 * route IS registered (B1.6 edition-gated registration). OSS-absent is asserted by the firewall test.
 */
final class HostedEditionRouteTest extends TestCase
{
    /** @var string|false */
    private string|false $savedEdition = false;

    protected function setUp(): void
    {
        $this->savedEdition = $_SERVER['NENE_SUITE_EDITION'] ?? false;
        // .env uses immutable load, so setting it here (before container build) wins.
        $_SERVER['NENE_SUITE_EDITION'] = 'hosted';
    }

    protected function tearDown(): void
    {
        if ($this->savedEdition === false) {
            unset($_SERVER['NENE_SUITE_EDITION'], $_ENV['NENE_SUITE_EDITION']);
        } else {
            $_SERVER['NENE_SUITE_EDITION'] = $this->savedEdition;
        }
    }

    public function testHostedEditionServesTheJwksRoute(): void
    {
        $container = (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();
        $registrars = $container->get(ApplicationServiceProvider::ROUTE_REGISTRARS);
        self::assertIsArray($registrars);

        $router = new Router();
        foreach ($registrars as $registrar) {
            self::assertIsCallable($registrar);
            $registrar($router);
        }

        $routesProperty = new ReflectionProperty(Router::class, 'routes');
        $rawRoutes = $routesProperty->getValue($router);
        self::assertIsArray($rawRoutes);

        $paths = [];
        foreach ($rawRoutes as $route) {
            if (is_array($route) && is_string($route['path'] ?? null)) {
                $paths[] = $route['path'];
            }
        }

        self::assertContains('/.well-known/jwks.json', $paths, 'hosted edition must serve the JWKS route');
    }
}

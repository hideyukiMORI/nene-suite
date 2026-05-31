<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\OpenApi;

use Nene2\Routing\Router;
use NeNeSuite\ApplicationServiceProvider;
use NeNeSuite\Http\RuntimeContainerFactory;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Yaml\Yaml;

/**
 * Guards against drift between the routes the application actually serves and the
 * docs/openapi/openapi.yaml contract (API-first, ADR/#18). Structural YAML rules
 * live in tools/validate-openapi.php (`composer openapi`).
 */
final class OpenApiContractTest extends TestCase
{
    /**
     * operationIds whose routes are implemented and MUST be served. Documented
     * but not-yet-implemented operations (e.g. listInstalledApps) are excluded.
     *
     * @var list<string>
     */
    private const IMPLEMENTED_OPERATION_IDS = [
        'listCatalogApps',
        'startInstallSession',
        'getInstallSession',
        'updateAppSelection',
        'acceptDisclaimer',
        'completeInstallSession',
        'failInstallSession',
        'createAuthSession',
        'getAuthSession',
        'deleteAuthSession',
        'listSuiteAuditEvents',
        'listInstalledApps',
        'createOperator',
    ];

    public function testEveryServedApiRouteIsDocumentedWithOperationId(): void
    {
        $spec = $this->openapi();

        foreach ($this->registeredRoutes() as $method => $paths) {
            foreach ($paths as $path) {
                $operation = $spec['paths'][$path][$method] ?? null;

                self::assertIsArray(
                    $operation,
                    sprintf('Route %s %s is served but not documented in openapi.yaml.', strtoupper($method), $path),
                );
                self::assertArrayHasKey(
                    'operationId',
                    $operation,
                    sprintf('Documented route %s %s has no operationId.', strtoupper($method), $path),
                );
            }
        }
    }

    public function testImplementedOperationIdsAreServed(): void
    {
        $spec = $this->openapi();

        // operationId => "method path" from the contract.
        $documented = [];
        foreach ($spec['paths'] as $path => $item) {
            if (!is_array($item) || !is_string($path)) {
                continue;
            }
            foreach ($item as $method => $operation) {
                if (is_array($operation) && is_string($operation['operationId'] ?? null)) {
                    $documented[$operation['operationId']] = $method . ' ' . $path;
                }
            }
        }

        $served = [];
        foreach ($this->registeredRoutes() as $method => $paths) {
            foreach ($paths as $path) {
                $served[$method . ' ' . $path] = true;
            }
        }

        foreach (self::IMPLEMENTED_OPERATION_IDS as $operationId) {
            self::assertArrayHasKey($operationId, $documented, "operationId {$operationId} is missing from openapi.yaml.");
            self::assertArrayHasKey(
                $documented[$operationId],
                $served,
                "operationId {$operationId} ({$documented[$operationId]}) is documented but not served by any route registrar.",
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function openapi(): array
    {
        $parsed = Yaml::parseFile(dirname(__DIR__, 2) . '/docs/openapi/openapi.yaml');
        self::assertIsArray($parsed);
        self::assertIsArray($parsed['paths'] ?? null);

        /** @var array<string, mixed> $parsed */
        return $parsed;
    }

    /**
     * Applies the application's route registrars to a Router and returns the
     * served `/api/v1` routes as method => list<path>.
     *
     * @return array<string, list<string>>
     */
    private function registeredRoutes(): array
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

        $routes = [];
        foreach ($rawRoutes as $route) {
            if (!is_array($route)) {
                continue;
            }
            $method = $route['method'] ?? null;
            $path = $route['path'] ?? null;
            if (!is_string($method) || !is_string($path) || !str_starts_with($path, '/api/v1')) {
                continue;
            }
            $routes[strtolower($method)][] = $path;
        }

        return $routes;
    }
}

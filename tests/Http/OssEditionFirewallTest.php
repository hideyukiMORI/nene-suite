<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Http;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Auth\TokenVerifierInterface;
use Nene2\Routing\Router;
use NeNeSuite\ApplicationServiceProvider;
use NeNeSuite\Http\Edition;
use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Http\RuntimeServiceProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

/**
 * OSS-flags-off firewall (Phase B1.1). Boots the composition root with the default (self-hosted)
 * edition and asserts the invariants the later hosted/IdP slices (B1.4–B1.6) must never regress:
 * the edition resolves to Oss, the apex token path stays the HS256 LocalBearerTokenVerifier (no
 * ES256 alg-confusion substitution), and no `/.well-known/*` federation route is served. This is a
 * standing gate — a hosted-only service or route accidentally constructed in OSS turns it red.
 */
final class OssEditionFirewallTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $savedEnv = [];

    protected function setUp(): void
    {
        // Make the OSS path deterministic regardless of ambient/.env: the edition must NOT be
        // hosted. (We do not clear NENE_SUITE_ALLOW_DEV_SECRET — the apex verifier legitimately
        // uses the dev secret here; the fail-closed path is covered by JwtSecretResolverTest.)
        foreach (['NENE_SUITE_EDITION', 'NENE_SUITE_FEDERATION_PRIVATE_KEY', 'NENE_SUITE_FED_KEY_ENC_KEY'] as $key) {
            $this->savedEnv[$key] = $_SERVER[$key] ?? false;
            unset($_SERVER[$key], $_ENV[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->savedEnv as $key => $value) {
            if ($value === false) {
                unset($_SERVER[$key], $_ENV[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }
    }

    public function testEditionDefaultsToOss(): void
    {
        $edition = $this->container()->get(RuntimeServiceProvider::EDITION);

        self::assertSame(Edition::Oss, $edition);
    }

    public function testApexTokenVerifierStaysHs256LocalVerifier(): void
    {
        $verifier = $this->container()->get(TokenVerifierInterface::class);

        // The federation ES256 verifier (B1.4) must be a SEPARATE binding; the apex path must
        // never resolve to it (alg-confusion / trust-domain crossing).
        self::assertInstanceOf(LocalBearerTokenVerifier::class, $verifier);
    }

    public function testNoWellKnownFederationRouteIsServedInOss(): void
    {
        $container = $this->container();
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

        foreach ($rawRoutes as $route) {
            if (!is_array($route)) {
                continue;
            }
            $path = $route['path'] ?? null;
            if (is_string($path)) {
                self::assertStringStartsNotWith('/.well-known', $path, 'OSS must not serve a federation JWKS route');
            }
        }
    }

    private function container(): ContainerInterface
    {
        return (new RuntimeContainerFactory(dirname(__DIR__, 2)))->create();
    }
}

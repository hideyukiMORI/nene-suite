<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use Nene2\Auth\LocalBearerTokenVerifier;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use NeNeSuite\Auth\OperatorSessionContextResolver;
use NeNeSuite\Tenancy\SuperadminGuard;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Shared scaffolding for the organization HTTP handler tests: a SuperadminGuard backed by a
 * real bearer verifier, superadmin/non-superadmin token minting, and PSR-7 request building.
 */
trait OrganizationHttpTestSupport
{
    private const TOKEN_SECRET = 'test-secret';
    private const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';

    private function guard(): SuperadminGuard
    {
        return new SuperadminGuard(new BearerTokenAuthenticator(
            new LocalBearerTokenVerifier(self::TOKEN_SECRET),
            new OperatorSessionContextResolver(new InMemoryMembershipRepository(), new InMemoryOrganizationRepository()),
        ));
    }

    private function superadminToken(): string
    {
        return $this->token(true);
    }

    private function nonSuperadminToken(): string
    {
        return $this->token(false);
    }

    private function token(bool $superadmin): string
    {
        return (new LocalBearerTokenVerifier(self::TOKEN_SECRET))->issue([
            'sub' => self::OPERATOR_ID,
            'org_external_id' => null,
            'role' => null,
            'superadmin' => $superadmin,
            'exp' => time() + 60,
        ]);
    }

    /**
     * @param array<string, mixed>  $body
     * @param array<string, string> $params
     */
    private function request(string $method, string $path, ?string $token, array $body = [], array $params = []): ServerRequestInterface
    {
        $factory = new Psr17Factory();
        $request = $factory->createServerRequest($method, $path);

        if ($token !== null) {
            $request = $request->withHeader('Authorization', "Bearer {$token}");
        }

        if ($params !== []) {
            $request = $request->withAttribute(Router::PARAMETERS_ATTRIBUTE, $params);
        }

        if ($body !== []) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($factory->createStream((string) json_encode($body)));
        }

        return $request;
    }

    private function jsonResponseFactory(): JsonResponseFactory
    {
        $psr17 = new Psr17Factory();

        return new JsonResponseFactory($psr17, $psr17);
    }

    private function requestIdHolder(): RequestIdHolder
    {
        return new RequestIdHolder();
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(ResponseInterface $response): array
    {
        $decoded = json_decode((string) $response->getBody(), true);
        self::assertIsArray($decoded);

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}

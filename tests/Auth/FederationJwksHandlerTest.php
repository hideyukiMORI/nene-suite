<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\FederationJwksHandler;
use NeNeSuite\Auth\FederationSigningKey;
use NeNeSuite\Auth\FederationSigningKeyStatus;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class FederationJwksHandlerTest extends TestCase
{
    private const NOW = '2026-06-22T00:00:00Z';

    public function testPublishesActiveAndRetiringPublicKeysOnly(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TC', 'kid-active', FederationSigningKeyStatus::Active));
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TD', 'kid-retiring', FederationSigningKeyStatus::Retiring));
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TE', 'kid-retired', FederationSigningKeyStatus::Retired));
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TF', 'kid-revoked', FederationSigningKeyStatus::Revoked));

        $response = $this->handler($repository)->handle($this->request());

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertIsArray($body['keys']);

        $kids = array_map(static fn (array $jwk): mixed => $jwk['kid'], $body['keys']);
        self::assertContains('kid-active', $kids);
        self::assertContains('kid-retiring', $kids);
        self::assertNotContains('kid-retired', $kids);
        self::assertNotContains('kid-revoked', $kids);

        self::assertStringContainsString('max-age', $response->getHeaderLine('Cache-Control'));
        self::assertNotSame('', $response->getHeaderLine('ETag'));
        // JWKS exposes public material only.
        self::assertStringNotContainsString('"d"', (string) $response->getBody());
        self::assertStringNotContainsString('PRIVATE', (string) $response->getBody());
    }

    public function testEmptyKeySetWhenNoPublishableKeys(): void
    {
        $response = $this->handler(new InMemoryFederationSigningKeyRepository())->handle($this->request());

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string) $response->getBody(), true);
        self::assertIsArray($body);
        self::assertSame([], $body['keys']);
    }

    private function key(string $id, string $kid, FederationSigningKeyStatus $status): FederationSigningKey
    {
        $jwk = (string) json_encode([
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => 'f83OJ3D2xF1Bg8vub9tLe1gHMzV76e8Tus9uPHvRVEU',
            'y' => 'x_FEzRu9m36HLN_tue659LNpXW6pCyStikYjKIWI5a0',
            'use' => 'sig',
            'alg' => 'ES256',
            'kid' => $kid,
        ]);

        return new FederationSigningKey($id, $kid, 'ES256', $jwk, $status, self::NOW, self::NOW, null);
    }

    private function handler(InMemoryFederationSigningKeyRepository $repository): FederationJwksHandler
    {
        $psr17 = new Psr17Factory();

        return new FederationJwksHandler($repository, new JsonResponseFactory($psr17, $psr17));
    }

    private function request(): ServerRequestInterface
    {
        return (new Psr17Factory())->createServerRequest('GET', '/.well-known/jwks.json');
    }
}

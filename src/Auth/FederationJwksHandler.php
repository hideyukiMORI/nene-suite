<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /.well-known/jwks.json — operationId getFederationJwks. Publishes the suite's federation
 * signing **public** keys (active + retiring) as a JWKS so siblings can verify ES256 assertions
 * and refresh on an unknown `kid` (ADR 0012 §3). Unauthenticated and cacheable — the body is a pure
 * function of key state. Registered ONLY in the hosted edition (the route is absent in OSS → 404).
 */
final readonly class FederationJwksHandler
{
    private const CACHE_MAX_AGE_SECONDS = 300;

    public function __construct(
        private FederationSigningKeyRepositoryInterface $keys,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $keys = [];

        foreach ($this->keys->findPublishable() as $key) {
            $jwk = json_decode($key->publicJwk, true);

            if (is_array($jwk)) {
                $keys[] = $jwk;
            }
        }

        $payload = ['keys' => $keys];
        $etag = '"' . substr(hash('sha256', (string) json_encode($payload)), 0, 32) . '"';

        return $this->response
            ->create($payload)
            ->withHeader('Cache-Control', 'public, max-age=' . self::CACHE_MAX_AGE_SECONDS)
            ->withHeader('ETag', $etag);
    }
}

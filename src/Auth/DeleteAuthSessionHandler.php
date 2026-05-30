<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * DELETE /api/v1/auth/session — operationId deleteAuthSession.
 *
 * JWTs are stateless in Phase 1, so logout is a client-side token discard; the
 * server only confirms the caller is authenticated and returns 204. Server-side
 * revocation MAY be added in Phase 2.
 */
final readonly class DeleteAuthSessionHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticator->operatorId($request);

        return $this->response->createEmpty(204);
    }
}

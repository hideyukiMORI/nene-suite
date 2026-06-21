<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/auth/session — operationId getAuthSession.
 */
final readonly class GetAuthSessionHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private GetAuthSessionUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $principal = $this->authenticator->principal($request);
        $operator = $this->useCase->execute($principal->operatorId);

        return $this->response->create([
            ...OperatorView::toArray($operator),
            'orgExternalId' => $principal->orgExternalId,
            'role' => $principal->role?->value,
            'superadmin' => $principal->isSuperadmin,
        ]);
    }
}

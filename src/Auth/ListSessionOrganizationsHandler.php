<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/auth/session/organizations — operationId listSessionOrganizations. Operator
 * self-service: returns the organizations the bearer-token operator belongs to, to drive the
 * active-organization switcher. Read-only; no audit event.
 */
final readonly class ListSessionOrganizationsHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private ListSessionOrganizationsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $operatorId = $this->authenticator->operatorId($request);

        $output = $this->useCase->execute($operatorId);

        return $this->response->create([
            'organizations' => array_map(
                static fn (SessionOrganization $organization): array => SessionOrganizationView::toArray($organization),
                $output->organizations,
            ),
        ]);
    }
}

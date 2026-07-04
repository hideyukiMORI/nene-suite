<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/machine/deploy/requests/pending — operationId listPendingDeployRequests.
 * Machine seam: authenticated by the deploy agent key, never an operator token.
 */
final readonly class ListPendingDeployRequestsHandler
{
    public function __construct(
        private DeployAgentAuthenticator $authenticator,
        private ListPendingDeployRequestsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticator->ensure($request);

        $output = $this->useCase->execute();

        return $this->response->create([
            'requests' => array_map(
                static fn (DeployRequest $deployRequest): array => DeployRequestView::toArray($deployRequest),
                $output->requests,
            ),
        ]);
    }
}

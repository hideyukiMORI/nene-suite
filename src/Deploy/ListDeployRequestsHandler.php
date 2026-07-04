<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Tenancy\SuperadminGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/deploy/requests — operationId listDeployRequests. Platform-superadmin only.
 * Always 200; `enabled` lets the UI degrade without a second probe.
 */
final readonly class ListDeployRequestsHandler
{
    private const DEFAULT_LIMIT = 50;

    private const MAX_LIMIT = 100;

    public function __construct(
        private SuperadminGuard $guard,
        private ListDeployRequestsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        $query = $request->getQueryParams();
        $status = is_string($query['status'] ?? null) ? DeployRequestStatus::tryFrom($query['status']) : null;

        $limit = self::DEFAULT_LIMIT;
        $rawLimit = $query['limit'] ?? null;

        if (is_string($rawLimit) && ctype_digit($rawLimit)) {
            $limit = max(1, min(self::MAX_LIMIT, (int) $rawLimit));
        }

        $output = $this->useCase->execute(new ListDeployRequestsInput($status, $limit));

        return $this->response->create([
            'enabled' => $output->enabled,
            'requests' => array_map(
                static fn (DeployRequest $deployRequest): array => DeployRequestView::toArray($deployRequest),
                $output->requests,
            ),
        ]);
    }
}

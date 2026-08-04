<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\UtcClock;
use NeNeSuite\Tenancy\SuperadminGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/deploy/plan — operationId getDeployPlan. Platform-superadmin only.
 * Read-only plan computation; always 200 (see the DeployPlan flags).
 */
final readonly class GetDeployPlanHandler
{
    public function __construct(
        private SuperadminGuard $guard,
        private ComputeDeployPlanUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        return $this->response->create($this->useCase->execute($this->clock->now())->toArray());
    }
}

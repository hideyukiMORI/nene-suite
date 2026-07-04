<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeployRouteRegistrar
{
    public function __construct(
        private CreateDeployRequestHandler $createHandler,
        private ListDeployRequestsHandler $listHandler,
        private GetDeployPlanHandler $planHandler,
        private ListPendingDeployRequestsHandler $listPendingHandler,
        private ReportDeployRequestResultHandler $reportResultHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $createHandler = $this->createHandler;
        $listHandler = $this->listHandler;
        $planHandler = $this->planHandler;
        $listPendingHandler = $this->listPendingHandler;
        $reportResultHandler = $this->reportResultHandler;

        $router->post(
            '/api/v1/deploy/requests',
            static fn (ServerRequestInterface $request) => $createHandler->handle($request),
        );

        $router->get(
            '/api/v1/deploy/requests',
            static fn (ServerRequestInterface $request) => $listHandler->handle($request),
        );

        $router->get(
            '/api/v1/deploy/plan',
            static fn (ServerRequestInterface $request) => $planHandler->handle($request),
        );

        $router->get(
            '/api/v1/machine/deploy/requests/pending',
            static fn (ServerRequestInterface $request) => $listPendingHandler->handle($request),
        );

        $router->put(
            '/api/v1/machine/deploy/requests/{id}/result',
            static fn (ServerRequestInterface $request) => $reportResultHandler->handle($request),
        );
    }
}

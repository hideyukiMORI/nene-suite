<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class SuiteAuditRouteRegistrar
{
    public function __construct(
        private ListSuiteAuditEventsHandler $listHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;

        $router->get(
            '/api/v1/suite-audit-events',
            static fn (ServerRequestInterface $request) => $listHandler->handle($request),
        );
    }
}

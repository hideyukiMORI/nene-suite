<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseTargets;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DatabaseTargetsRouteRegistrar
{
    public function __construct(
        private SetDatabaseTargetsHandler $setHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $setHandler = $this->setHandler;

        $router->put(
            '/api/v1/install-sessions/{installSessionId}/database-targets',
            static fn (ServerRequestInterface $request) => $setHandler->handle($request),
        );
    }
}

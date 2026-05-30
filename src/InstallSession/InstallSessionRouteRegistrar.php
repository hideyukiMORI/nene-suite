<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class InstallSessionRouteRegistrar
{
    public function __construct(
        private StartInstallSessionHandler $startHandler,
        private GetInstallSessionHandler $getHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $startHandler = $this->startHandler;
        $getHandler = $this->getHandler;

        $router->post(
            '/api/v1/install-sessions',
            static fn (ServerRequestInterface $request) => $startHandler->handle($request),
        );
        $router->get(
            '/api/v1/install-sessions/{installSessionId}',
            static fn (ServerRequestInterface $request) => $getHandler->handle($request),
        );
    }
}

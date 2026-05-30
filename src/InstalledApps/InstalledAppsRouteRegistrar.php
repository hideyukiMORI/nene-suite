<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class InstalledAppsRouteRegistrar
{
    public function __construct(
        private ListInstalledAppsHandler $listHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;

        $router->get(
            '/api/v1/installed-apps',
            static fn (ServerRequestInterface $request) => $listHandler->handle($request),
        );
    }
}

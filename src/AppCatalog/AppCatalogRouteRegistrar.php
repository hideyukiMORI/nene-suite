<?php

declare(strict_types=1);

namespace NeNeSuite\AppCatalog;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AppCatalogRouteRegistrar
{
    public function __construct(
        private ListCatalogAppsHandler $listHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $listHandler = $this->listHandler;

        $router->get(
            '/api/v1/catalog/apps',
            static fn (ServerRequestInterface $request) => $listHandler->handle($request),
        );
    }
}

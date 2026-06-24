<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class OriginRouteRegistrar
{
    public function __construct(
        private GetOriginUpdatesHandler $updatesHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $updatesHandler = $this->updatesHandler;

        $router->get(
            '/api/v1/origin/updates',
            static fn (ServerRequestInterface $request) => $updatesHandler->handle($request),
        );
    }
}

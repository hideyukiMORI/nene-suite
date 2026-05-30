<?php

declare(strict_types=1);

namespace NeNeSuite\AppSelection;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AppSelectionRouteRegistrar
{
    public function __construct(
        private UpdateAppSelectionHandler $updateHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $updateHandler = $this->updateHandler;

        $router->put(
            '/api/v1/install-sessions/{installSessionId}/app-selection',
            static fn (ServerRequestInterface $request) => $updateHandler->handle($request),
        );
    }
}

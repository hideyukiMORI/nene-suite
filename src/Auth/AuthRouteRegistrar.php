<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class AuthRouteRegistrar
{
    public function __construct(
        private CreateAuthSessionHandler $createHandler,
        private GetAuthSessionHandler $getHandler,
        private DeleteAuthSessionHandler $deleteHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $createHandler = $this->createHandler;
        $getHandler = $this->getHandler;
        $deleteHandler = $this->deleteHandler;

        $router->post(
            '/api/v1/auth/session',
            static fn (ServerRequestInterface $request) => $createHandler->handle($request),
        );
        $router->get(
            '/api/v1/auth/session',
            static fn (ServerRequestInterface $request) => $getHandler->handle($request),
        );
        $router->delete(
            '/api/v1/auth/session',
            static fn (ServerRequestInterface $request) => $deleteHandler->handle($request),
        );
    }
}

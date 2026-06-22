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
        private CreateOperatorHandler $createOperatorHandler,
        private ListOperatorsHandler $listOperatorsHandler,
        private ListSessionOrganizationsHandler $listSessionOrganizationsHandler,
        private SwitchActiveOrganizationHandler $switchActiveOrganizationHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $createHandler = $this->createHandler;
        $getHandler = $this->getHandler;
        $deleteHandler = $this->deleteHandler;
        $createOperatorHandler = $this->createOperatorHandler;
        $listOperatorsHandler = $this->listOperatorsHandler;
        $listSessionOrganizationsHandler = $this->listSessionOrganizationsHandler;
        $switchActiveOrganizationHandler = $this->switchActiveOrganizationHandler;

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
        $router->post(
            '/api/v1/operators',
            static fn (ServerRequestInterface $request) => $createOperatorHandler->handle($request),
        );
        $router->get(
            '/api/v1/operators',
            static fn (ServerRequestInterface $request) => $listOperatorsHandler->handle($request),
        );
        $router->get(
            '/api/v1/auth/session/organizations',
            static fn (ServerRequestInterface $request) => $listSessionOrganizationsHandler->handle($request),
        );
        $router->put(
            '/api/v1/auth/session/active-organization',
            static fn (ServerRequestInterface $request) => $switchActiveOrganizationHandler->handle($request),
        );
    }
}

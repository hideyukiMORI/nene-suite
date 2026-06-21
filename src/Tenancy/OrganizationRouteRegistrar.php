<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class OrganizationRouteRegistrar
{
    public function __construct(
        private CreateOrganizationHandler $createHandler,
        private ListOrganizationsHandler $listHandler,
        private RenameOrganizationHandler $renameHandler,
        private DisableOrganizationHandler $disableHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $create = $this->createHandler;
        $list = $this->listHandler;
        $rename = $this->renameHandler;
        $disable = $this->disableHandler;

        $router
            ->post('/api/v1/organizations', static fn (ServerRequestInterface $request) => $create->handle($request))
            ->get('/api/v1/organizations', static fn (ServerRequestInterface $request) => $list->handle($request))
            ->patch('/api/v1/organizations/{id}', static fn (ServerRequestInterface $request) => $rename->handle($request))
            ->post('/api/v1/organizations/{id}/disable', static fn (ServerRequestInterface $request) => $disable->handle($request));
    }
}

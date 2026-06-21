<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class MembershipRouteRegistrar
{
    public function __construct(
        private ListOrganizationMembershipsHandler $listHandler,
        private GrantMembershipHandler $grantHandler,
        private ChangeMembershipRoleHandler $changeRoleHandler,
        private RevokeMembershipHandler $revokeHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $list = $this->listHandler;
        $grant = $this->grantHandler;
        $change = $this->changeRoleHandler;
        $revoke = $this->revokeHandler;

        $router
            ->get('/api/v1/organizations/{id}/memberships', static fn (ServerRequestInterface $request) => $list->handle($request))
            ->post('/api/v1/organizations/{id}/memberships', static fn (ServerRequestInterface $request) => $grant->handle($request))
            ->patch('/api/v1/memberships/{id}', static fn (ServerRequestInterface $request) => $change->handle($request))
            ->delete('/api/v1/memberships/{id}', static fn (ServerRequestInterface $request) => $revoke->handle($request));
    }
}

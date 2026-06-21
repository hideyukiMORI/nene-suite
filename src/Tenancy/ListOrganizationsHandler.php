<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/organizations — operationId listOrganizations. Platform-superadmin only.
 */
final readonly class ListOrganizationsHandler
{
    public function __construct(
        private SuperadminGuard $guard,
        private ListOrganizationsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        $output = $this->useCase->execute();

        return $this->response->create([
            'organizations' => array_map(
                static fn (Organization $organization): array => OrganizationView::toArray($organization),
                $output->organizations,
            ),
        ]);
    }
}

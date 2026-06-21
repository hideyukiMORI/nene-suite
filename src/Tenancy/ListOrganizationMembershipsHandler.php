<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/organizations/{id}/memberships — operationId listMemberships. Platform-superadmin
 * only. Returns the organization's members enriched with operator identity (milestone A8b).
 */
final readonly class ListOrganizationMembershipsHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SuperadminGuard $guard,
        private ListOrganizationMembershipsUseCaseInterface $useCase,
        private OrganizationRepositoryInterface $organizations,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $organizationId = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        if (preg_match(self::ULID_PATTERN, $organizationId) !== 1 || $this->organizations->findById($organizationId) === null) {
            throw new OrganizationNotFoundException($organizationId);
        }

        $output = $this->useCase->execute($organizationId);

        return $this->response->create([
            'members' => array_map(
                static fn (OrganizationMember $member): array => OrganizationMemberView::toArray($member),
                $output->members,
            ),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PATCH /api/v1/memberships/{id} — operationId changeMembershipRole. Platform-superadmin only.
 */
final readonly class ChangeMembershipRoleHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SuperadminGuard $guard,
        private ChangeMembershipRoleUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $membershipId = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        if (preg_match(self::ULID_PATTERN, $membershipId) !== 1) {
            throw new MembershipNotFoundException($membershipId);
        }

        $body = JsonRequestBodyParser::parse($request);
        $role = MembershipRoleParser::parse($body['role'] ?? null);

        $requestId = $this->requestIdHolder->get();
        $output = $this->useCase->execute(new ChangeMembershipRoleInput($membershipId, $role, $requestId !== '' ? $requestId : null));

        return $this->response->create(MembershipView::toArray($output->membership));
    }
}

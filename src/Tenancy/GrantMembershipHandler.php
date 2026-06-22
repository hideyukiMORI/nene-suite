<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use NeNeSuite\Auth\OperatorRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/organizations/{id}/memberships — operationId grantMembership. Platform-superadmin
 * only. The use case does not check referential existence (milestone A3 docblock), so this
 * handler resolves the organization from the path (404 if unknown) and verifies the body's
 * operatorId references an existing operator (422 if not) before granting.
 */
final readonly class GrantMembershipHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SuperadminGuard $guard,
        private GrantMembershipUseCaseInterface $useCase,
        private OrganizationRepositoryInterface $organizations,
        private OperatorRepositoryInterface $operators,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
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

        $body = JsonRequestBodyParser::parse($request);

        $operatorId = is_string($body['operatorId'] ?? null) ? trim((string) $body['operatorId']) : '';
        if (preg_match(self::ULID_PATTERN, $operatorId) !== 1) {
            throw new MembershipValidationException('operatorId', 'operatorId must be a valid ULID.');
        }

        if ($this->operators->findById($operatorId) === null) {
            throw new MembershipValidationException('operatorId', 'operatorId does not reference an existing operator.');
        }

        $role = MembershipRoleParser::parse($body['role'] ?? null);

        $requestId = $this->requestIdHolder->get();
        $output = $this->useCase->execute(new GrantMembershipInput(
            operatorId: $operatorId,
            role: $role,
            organizationId: $organizationId,
            requestId: $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(MembershipView::toArray($output->membership), 201);
    }
}

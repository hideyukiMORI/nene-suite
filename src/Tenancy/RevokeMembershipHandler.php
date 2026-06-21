<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * DELETE /api/v1/memberships/{id} — operationId revokeMembership. Platform-superadmin only.
 * Returns 204 No Content; the last-admin / last-superadmin invariant is enforced by the use case.
 */
final readonly class RevokeMembershipHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SuperadminGuard $guard,
        private RevokeMembershipUseCaseInterface $useCase,
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

        $requestId = $this->requestIdHolder->get();
        $this->useCase->execute(new RevokeMembershipInput($membershipId, $requestId !== '' ? $requestId : null));

        return $this->response->createEmpty(204);
    }
}

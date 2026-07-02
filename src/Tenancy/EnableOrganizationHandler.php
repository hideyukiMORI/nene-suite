<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/organizations/{id}/enable — operationId enableOrganization. Platform-superadmin
 * only. Reverses a soft-disable; already-active is an idempotent 200 (no body to parse).
 */
final readonly class EnableOrganizationHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SuperadminGuard $guard,
        private EnableOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        if (preg_match(self::ULID_PATTERN, $id) !== 1) {
            throw new OrganizationNotFoundException($id);
        }

        $requestId = $this->requestIdHolder->get();
        $output = $this->useCase->execute(new EnableOrganizationInput($id, $requestId !== '' ? $requestId : null));

        return $this->response->create(OrganizationView::toArray($output->organization));
    }
}

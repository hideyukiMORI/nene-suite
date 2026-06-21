<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/organizations/{id}/disable — operationId disableOrganization. Platform-superadmin
 * only. Soft-disable; already-disabled is an idempotent 200 (no body to parse).
 */
final readonly class DisableOrganizationHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SuperadminGuard $guard,
        private DisableOrganizationUseCaseInterface $useCase,
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
        $output = $this->useCase->execute(new DisableOrganizationInput($id, $requestId !== '' ? $requestId : null));

        return $this->response->create(OrganizationView::toArray($output->organization));
    }
}

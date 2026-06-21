<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PATCH /api/v1/organizations/{id} — operationId renameOrganization. Platform-superadmin only.
 */
final readonly class RenameOrganizationHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SuperadminGuard $guard,
        private RenameOrganizationUseCaseInterface $useCase,
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

        $body = JsonRequestBodyParser::parse($request);
        $name = is_string($body['name'] ?? null) ? trim((string) $body['name']) : '';

        if ($name === '') {
            throw new ValidationException([new ValidationError('name', 'name is required.', 'required')]);
        }

        $requestId = $this->requestIdHolder->get();
        $output = $this->useCase->execute(new RenameOrganizationInput($id, $name, $requestId !== '' ? $requestId : null));

        return $this->response->create(OrganizationView::toArray($output->organization));
    }
}

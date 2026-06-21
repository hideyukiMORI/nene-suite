<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/organizations — operationId createOrganization. Platform-superadmin only.
 */
final readonly class CreateOrganizationHandler
{
    public function __construct(
        private SuperadminGuard $guard,
        private CreateOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $name = is_string($body['name'] ?? null) ? trim((string) $body['name']) : '';
        $slug = is_string($body['slug'] ?? null) ? trim((string) $body['slug']) : '';

        if ($name === '') {
            $errors[] = new ValidationError('name', 'name is required.', 'required');
        }

        if ($slug === '') {
            $errors[] = new ValidationError('slug', 'slug is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $requestId = $this->requestIdHolder->get();
        $output = $this->useCase->execute(new CreateOrganizationInput($name, $slug, $requestId !== '' ? $requestId : null));

        return $this->response->create(OrganizationView::toArray($output->organization), 201);
    }
}

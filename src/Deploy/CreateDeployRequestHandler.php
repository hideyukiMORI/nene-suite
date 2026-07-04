<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeSuite\Tenancy\SuperadminGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/deploy/requests — operationId createDeployRequest. Platform-superadmin only.
 */
final readonly class CreateDeployRequestHandler
{
    public function __construct(
        private SuperadminGuard $guard,
        private CreateDeployRequestUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $principal = $this->guard->ensure($request);

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $service = is_string($body['service'] ?? null) ? trim((string) $body['service']) : '';
        $imageDigest = is_string($body['imageDigest'] ?? null) ? trim((string) $body['imageDigest']) : '';

        if ($service === '') {
            $errors[] = new ValidationError('service', 'service is required.', 'required');
        }

        if ($imageDigest === '') {
            $errors[] = new ValidationError('imageDigest', 'imageDigest is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $requestId = $this->requestIdHolder->get();
        $output = $this->useCase->execute(new CreateDeployRequestInput(
            $service,
            $imageDigest,
            $principal->operatorId,
            $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(DeployRequestView::toArray($output->request), 201);
    }
}

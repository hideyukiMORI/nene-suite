<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/operators — operationId createOperator.
 * Operator-authenticated. Creates an additional apex operator and audits
 * `apex_operator.created`.
 */
final readonly class CreateOperatorHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private ProvisionApexOperatorUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticator->operatorId($request);

        $body = JsonRequestBodyParser::parse($request);
        $errors = [];

        $email = is_string($body['email'] ?? null) ? trim((string) $body['email']) : '';
        $password = is_string($body['password'] ?? null) ? (string) $body['password'] : '';
        $displayName = isset($body['displayName']) && is_string($body['displayName'])
            ? (trim($body['displayName']) !== '' ? trim($body['displayName']) : null)
            : null;

        if ($email === '') {
            $errors[] = new ValidationError('email', 'email is required.', 'required');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'password is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateOperatorInput($email, $password, $displayName));

        return $this->response->create(OperatorView::toArray($output->operator), 201);
    }
}

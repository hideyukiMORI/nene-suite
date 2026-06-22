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
 * POST /api/v1/auth/session — operationId createAuthSession.
 */
final readonly class CreateAuthSessionHandler
{
    public function __construct(
        private CreateAuthSessionUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private ClientIpResolver $clientIpResolver,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $email = is_string($body['email'] ?? null) ? trim((string) $body['email']) : '';
        $password = is_string($body['password'] ?? null) ? (string) $body['password'] : '';

        if ($email === '') {
            $errors[] = new ValidationError('email', 'email is required.', 'required');
        }

        if ($password === '') {
            $errors[] = new ValidationError('password', 'password is required.', 'required');
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $output = $this->useCase->execute(new CreateAuthSessionInput($email, $password, $this->clientIpResolver->resolve($request)));

        return $this->response->create(
            [
                'token' => $output->token,
                'expiresAt' => gmdate('Y-m-d\TH:i:s\Z', $output->expiresAt),
                'operator' => OperatorView::toArray($output->operator),
                'orgExternalId' => $output->orgExternalId,
                'role' => $output->role?->value,
                'superadmin' => $output->superadmin,
            ],
            201,
        );
    }
}

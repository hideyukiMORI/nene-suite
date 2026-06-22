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
 * PUT /api/v1/auth/session/active-organization — operationId switchActiveOrganization. Operator
 * self-service: re-scopes the bearer-token operator's session to the requested organization and
 * returns a fresh apex JWT. The response mirrors createAuthSession (AuthSession).
 */
final readonly class SwitchActiveOrganizationHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private SwitchActiveOrganizationUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $operatorId = $this->authenticator->operatorId($request);

        $body = JsonRequestBodyParser::parse($request);
        $organizationId = is_string($body['organizationId'] ?? null) ? trim((string) $body['organizationId']) : '';

        if (preg_match(self::ULID_PATTERN, $organizationId) !== 1) {
            throw new ValidationException([
                new ValidationError('organizationId', 'organizationId must be a valid ULID.', 'invalid'),
            ]);
        }

        $output = $this->useCase->execute(new SwitchActiveOrganizationInput($operatorId, $organizationId));

        return $this->response->create([
            'token' => $output->token,
            'expiresAt' => gmdate('Y-m-d\TH:i:s\Z', $output->expiresAt),
            'operator' => OperatorView::toArray($output->operator),
            'orgExternalId' => $output->orgExternalId,
            'role' => $output->role?->value,
            'superadmin' => $output->superadmin,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\JsonResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * DELETE /api/v1/auth/session — operationId deleteAuthSession.
 *
 * Logout denylists the presented token's `jti` (Phase B1.3) so the stateless JWT can no longer
 * authenticate until it expires; the client still discards it. A pre-B1.3 token without a `jti`
 * carries nothing to revoke — the response is still 204 and the client discards it.
 */
final readonly class DeleteAuthSessionHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private RevokedTokenRepositoryInterface $revokedTokens,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $claims = $this->authenticator->claims($request);

        $jti = is_string($claims['jti'] ?? null) ? $claims['jti'] : '';

        if ($jti !== '') {
            $expiresAt = is_numeric($claims['exp'] ?? null) ? (int) $claims['exp'] : 0;
            $this->revokedTokens->revoke($jti, $expiresAt, gmdate('Y-m-d\TH:i:s\Z'), 'logout');
        }

        return $this->response->createEmpty(204);
    }
}

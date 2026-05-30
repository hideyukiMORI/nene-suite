<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Auth\TokenVerificationException;
use Nene2\Auth\TokenVerifierInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Extracts and verifies the apex operator bearer token from a request and returns
 * the operator id (`sub` claim). Used by authenticated handlers in place of a
 * global middleware while only a few endpoints require auth.
 */
final readonly class BearerTokenAuthenticator
{
    public function __construct(
        private TokenVerifierInterface $tokenVerifier,
    ) {
    }

    /**
     * @throws UnauthorizedException when the token is missing, malformed, or invalid
     */
    public function operatorId(ServerRequestInterface $request): string
    {
        $header = $request->getHeaderLine('Authorization');

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches) !== 1) {
            throw new UnauthorizedException();
        }

        try {
            $claims = $this->tokenVerifier->verify(trim($matches[1]));
        } catch (TokenVerificationException) {
            throw new UnauthorizedException();
        }

        $subject = $claims['sub'] ?? null;

        if (!is_string($subject) || $subject === '') {
            throw new UnauthorizedException();
        }

        return $subject;
    }
}

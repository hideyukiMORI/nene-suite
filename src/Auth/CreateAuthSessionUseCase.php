<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Auth\TokenIssuerInterface;

/**
 * Logs an operator in: verifies email + password and issues a short-lived apex
 * JWT (claims per terminology §5). Login is authentication, not an orchestration
 * mutation, so it is not audited (Phase 2 may add apex_operator events).
 */
final readonly class CreateAuthSessionUseCase implements CreateAuthSessionUseCaseInterface
{
    private const TOKEN_TTL_SECONDS = 86400;

    public function __construct(
        private OperatorRepositoryInterface $operators,
        private PasswordHasher $passwordHasher,
        private TokenIssuerInterface $tokenIssuer,
        private string $suiteId,
        private OperatorSessionContextResolver $sessionContext,
        private LoginRateLimiter $rateLimiter,
    ) {
    }

    public function execute(CreateAuthSessionInput $input): CreateAuthSessionOutput
    {
        // Per-IP rate limit (B1.2): checked before any credential work, so a blocked client gets
        // 429 without revealing whether the email exists.
        $this->rateLimiter->ensureWithinLimit($input->clientIp);

        $operator = $this->operators->findByEmail($input->email);

        if ($operator === null) {
            // Spend comparable time so unknown emails are not distinguishable by timing.
            $this->passwordHasher->verifyDummy($input->password);
            $this->rateLimiter->recordFailure($input->clientIp);

            throw new InvalidCredentialsException();
        }

        if (!$this->passwordHasher->verify($input->password, $operator->passwordHash)) {
            $this->rateLimiter->recordFailure($input->clientIp);

            throw new InvalidCredentialsException();
        }

        // Successful login clears the client's failure window.
        $this->rateLimiter->clear($input->clientIp);

        // Resolve the active-org context (milestone A6) and carry it in the JWT claims so the
        // authenticator can read role/org without a repo round-trip on every request.
        $context = $this->sessionContext->resolve($operator->id);

        $issuedAt = time();
        $expiresAt = $issuedAt + self::TOKEN_TTL_SECONDS;

        $token = $this->tokenIssuer->issue([
            'sub' => $operator->id,
            'suite_id' => $this->suiteId,
            'org_external_id' => $context->orgExternalId,
            'role' => $context->role?->value,
            'superadmin' => $context->isSuperadmin,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ]);

        return new CreateAuthSessionOutput(
            $token,
            $expiresAt,
            $operator,
            $context->orgExternalId,
            $context->role,
            $context->isSuperadmin,
        );
    }
}

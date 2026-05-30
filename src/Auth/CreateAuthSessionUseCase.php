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
    ) {
    }

    public function execute(CreateAuthSessionInput $input): CreateAuthSessionOutput
    {
        $operator = $this->operators->findByEmail($input->email);

        if ($operator === null) {
            // Spend comparable time so unknown emails are not distinguishable by timing.
            $this->passwordHasher->verifyDummy($input->password);

            throw new InvalidCredentialsException();
        }

        if (!$this->passwordHasher->verify($input->password, $operator->passwordHash)) {
            throw new InvalidCredentialsException();
        }

        $issuedAt = time();
        $expiresAt = $issuedAt + self::TOKEN_TTL_SECONDS;

        $token = $this->tokenIssuer->issue([
            'sub' => $operator->id,
            'suite_id' => $this->suiteId,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ]);

        return new CreateAuthSessionOutput($token, $expiresAt, $operator);
    }
}

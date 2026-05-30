<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use NeNeSuite\Auth\CreateAuthSessionInput;
use NeNeSuite\Auth\CreateAuthSessionUseCase;
use NeNeSuite\Auth\InvalidCredentialsException;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class CreateAuthSessionUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';

    public function testIssuesTokenForValidCredentials(): void
    {
        $hasher = new PasswordHasher();
        $operators = new InMemoryOperatorRepository();
        $operators->save($this->operator($hasher->hash('s3cret-pass')));
        $verifier = new LocalBearerTokenVerifier('test-secret');

        $useCase = new CreateAuthSessionUseCase($operators, $hasher, $verifier, self::SUITE_ID);
        $output = $useCase->execute(new CreateAuthSessionInput('operator@example.com', 's3cret-pass'));

        self::assertSame(self::OPERATOR_ID, $output->operator->id);
        self::assertGreaterThan(time(), $output->expiresAt);

        $claims = $verifier->verify($output->token);
        self::assertSame(self::OPERATOR_ID, $claims['sub']);
        self::assertSame(self::SUITE_ID, $claims['suite_id']);
    }

    public function testRejectsWrongPassword(): void
    {
        $hasher = new PasswordHasher();
        $operators = new InMemoryOperatorRepository();
        $operators->save($this->operator($hasher->hash('s3cret-pass')));

        $useCase = new CreateAuthSessionUseCase($operators, $hasher, new LocalBearerTokenVerifier('test-secret'), self::SUITE_ID);

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new CreateAuthSessionInput('operator@example.com', 'wrong'));
    }

    public function testRejectsUnknownEmail(): void
    {
        $useCase = new CreateAuthSessionUseCase(
            new InMemoryOperatorRepository(),
            new PasswordHasher(),
            new LocalBearerTokenVerifier('test-secret'),
            self::SUITE_ID,
        );

        $this->expectException(InvalidCredentialsException::class);

        $useCase->execute(new CreateAuthSessionInput('nobody@example.com', 'whatever'));
    }

    private function operator(string $passwordHash): Operator
    {
        return new Operator(
            id: self::OPERATOR_ID,
            email: 'operator@example.com',
            passwordHash: $passwordHash,
            displayName: 'Example Operator',
            createdAt: '2026-05-30T09:48:46Z',
            updatedAt: '2026-05-30T09:48:46Z',
        );
    }
}

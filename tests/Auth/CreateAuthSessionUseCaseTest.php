<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Nene2\Auth\LocalBearerTokenVerifier;
use NeNeSuite\Auth\CreateAuthSessionInput;
use NeNeSuite\Auth\CreateAuthSessionUseCase;
use NeNeSuite\Auth\InvalidCredentialsException;
use NeNeSuite\Auth\LoginRateLimitedException;
use NeNeSuite\Auth\LoginRateLimiter;
use NeNeSuite\Auth\Operator;
use NeNeSuite\Auth\OperatorSessionContextResolver;
use NeNeSuite\Auth\PasswordHasher;
use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationStatus;
use NeNeSuite\Tenancy\Role;
use NeNeSuite\Tests\Tenancy\InMemoryMembershipRepository;
use NeNeSuite\Tests\Tenancy\InMemoryOrganizationRepository;
use PHPUnit\Framework\TestCase;

final class CreateAuthSessionUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const OPERATOR_ID = '01J8XR0G7Q9V2H7K3N5M0B8TCA';
    private const ORG_ID = '01J8XRDORG00000000000000ZA';
    private const ORG_EXTERNAL_ID = '01J8XRDEXT0000000000000ZAB';
    private const NOW = '2026-05-30T09:48:46Z';

    public function testIssuesTokenWithNullContextForOperatorWithoutMemberships(): void
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save($this->operator((new PasswordHasher())->hash('s3cret-pass')));
        $verifier = new LocalBearerTokenVerifier('test-secret');

        $output = $this->useCase($operators, $verifier)
            ->execute(new CreateAuthSessionInput('operator@example.com', 's3cret-pass'));

        self::assertSame(self::OPERATOR_ID, $output->operator->id);
        self::assertGreaterThan(time(), $output->expiresAt);
        self::assertNull($output->orgExternalId);
        self::assertNull($output->role);
        self::assertFalse($output->superadmin);

        $claims = $verifier->verify($output->token);
        self::assertSame(self::OPERATOR_ID, $claims['sub']);
        self::assertSame(self::SUITE_ID, $claims['suite_id']);
        self::assertNull($claims['org_external_id']);
        self::assertNull($claims['role']);
        self::assertFalse($claims['superadmin']);
    }

    public function testIssuesTokenWithActiveOrgContextAndSuperadmin(): void
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save($this->operator((new PasswordHasher())->hash('s3cret-pass')));
        $verifier = new LocalBearerTokenVerifier('test-secret');

        $memberships = new InMemoryMembershipRepository();
        $memberships->save(new Membership('01J0SUP', self::OPERATOR_ID, null, Role::Superadmin, self::NOW, self::NOW));
        $memberships->save(new Membership('01J0ADM', self::OPERATOR_ID, self::ORG_ID, Role::Admin, self::NOW, self::NOW));

        $output = $this->useCase($operators, $verifier, $memberships, $this->organizations())
            ->execute(new CreateAuthSessionInput('operator@example.com', 's3cret-pass'));

        self::assertSame(self::ORG_EXTERNAL_ID, $output->orgExternalId);
        self::assertSame(Role::Admin, $output->role);
        self::assertTrue($output->superadmin);

        $claims = $verifier->verify($output->token);
        self::assertSame(self::ORG_EXTERNAL_ID, $claims['org_external_id']);
        self::assertSame('admin', $claims['role']);
        self::assertTrue($claims['superadmin']);
    }

    public function testRejectsWrongPassword(): void
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save($this->operator((new PasswordHasher())->hash('s3cret-pass')));

        $this->expectException(InvalidCredentialsException::class);

        $this->useCase($operators, new LocalBearerTokenVerifier('test-secret'))
            ->execute(new CreateAuthSessionInput('operator@example.com', 'wrong'));
    }

    public function testRejectsUnknownEmail(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $this->useCase(new InMemoryOperatorRepository(), new LocalBearerTokenVerifier('test-secret'))
            ->execute(new CreateAuthSessionInput('nobody@example.com', 'whatever'));
    }

    public function testRejectsLoginFromRateLimitedClientIp(): void
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save($this->operator((new PasswordHasher())->hash('s3cret-pass')));

        // Pre-fill the client IP to the cap; even correct credentials must be refused with 429.
        $attempts = new InMemoryLoginAttemptRepository();
        $now = time();
        $attempts->recordFailure('ip:203.0.113.7', 900, $now);
        $attempts->recordFailure('ip:203.0.113.7', 900, $now);

        $this->expectException(LoginRateLimitedException::class);

        $this->useCase($operators, new LocalBearerTokenVerifier('test-secret'), rateLimiter: new LoginRateLimiter($attempts, 2, 900))
            ->execute(new CreateAuthSessionInput('operator@example.com', 's3cret-pass', '203.0.113.7'));
    }

    public function testSuccessfulLoginClearsTheClientIpWindow(): void
    {
        $operators = new InMemoryOperatorRepository();
        $operators->save($this->operator((new PasswordHasher())->hash('s3cret-pass')));

        $attempts = new InMemoryLoginAttemptRepository();
        $now = time();
        $attempts->recordFailure('ip:203.0.113.7', 900, $now);
        $limiter = new LoginRateLimiter($attempts, 3, 900);

        $this->useCase($operators, new LocalBearerTokenVerifier('test-secret'), rateLimiter: $limiter)
            ->execute(new CreateAuthSessionInput('operator@example.com', 's3cret-pass', '203.0.113.7'));

        self::assertSame(0, $attempts->countWithinWindow('ip:203.0.113.7', 900, $now));
    }

    private function useCase(
        InMemoryOperatorRepository $operators,
        LocalBearerTokenVerifier $verifier,
        ?InMemoryMembershipRepository $memberships = null,
        ?InMemoryOrganizationRepository $organizations = null,
        ?LoginRateLimiter $rateLimiter = null,
    ): CreateAuthSessionUseCase {
        return new CreateAuthSessionUseCase(
            $operators,
            new PasswordHasher(),
            $verifier,
            self::SUITE_ID,
            new OperatorSessionContextResolver(
                $memberships ?? new InMemoryMembershipRepository(),
                $organizations ?? new InMemoryOrganizationRepository(),
            ),
            $rateLimiter ?? new LoginRateLimiter(new InMemoryLoginAttemptRepository()),
        );
    }

    private function organizations(): InMemoryOrganizationRepository
    {
        $organizations = new InMemoryOrganizationRepository();
        $organizations->save(new Organization(self::ORG_ID, self::ORG_EXTERNAL_ID, 'Acme KK', 'acme-kk', OrganizationStatus::Active, self::NOW, self::NOW));

        return $organizations;
    }

    private function operator(string $passwordHash): Operator
    {
        return new Operator(
            id: self::OPERATOR_ID,
            email: 'operator@example.com',
            passwordHash: $passwordHash,
            displayName: 'Example Operator',
            createdAt: self::NOW,
            updatedAt: self::NOW,
        );
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\CreateOperatorInput;
use NeNeSuite\Auth\CreateOperatorUseCase;
use NeNeSuite\Auth\OperatorEmailConflictException;
use NeNeSuite\Auth\OperatorValidationException;
use NeNeSuite\Auth\PasswordHasher;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use PHPUnit\Framework\TestCase;

final class CreateOperatorUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';

    public function testCreatesOperatorSavesAndAudits(): void
    {
        $operators = new InMemoryOperatorRepository();
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($operators, $recorder)
            ->execute(new CreateOperatorInput('admin@example.com', 'correcthorsebatterystaple', 'Admin'));

        self::assertSame('admin@example.com', $output->operator->email);
        self::assertSame('Admin', $output->operator->displayName);
        self::assertNotEmpty($output->operator->id);
        self::assertNotEmpty($output->operator->passwordHash);

        $saved = $operators->findByEmail('admin@example.com');
        self::assertNotNull($saved);
        self::assertSame($output->operator->id, $saved->id);

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('apex_operator.created', $event->action);
        self::assertSame('apex_operator', $event->entityType);
        self::assertSame($output->operator->id, $event->entityId);
        self::assertNull($event->beforeJson);
        self::assertNotNull($event->afterJson);
        self::assertSame('admin@example.com', $event->afterJson['email']);
        self::assertSame('Admin', $event->afterJson['displayName']);
        self::assertArrayNotHasKey('passwordHash', $event->afterJson);
    }

    public function testPasswordIsHashed(): void
    {
        $operators = new InMemoryOperatorRepository();
        $hasher = new PasswordHasher();

        $this->useCase($operators)->execute(new CreateOperatorInput('op@example.com', 'supersecretpass1'));

        $saved = $operators->findByEmail('op@example.com');
        self::assertNotNull($saved);
        self::assertTrue($hasher->verify('supersecretpass1', $saved->passwordHash));
        self::assertStringNotContainsString('supersecretpass1', $saved->passwordHash);
    }

    public function testThrowsConflictForDuplicateEmail(): void
    {
        $operators = new InMemoryOperatorRepository();
        $useCase = $this->useCase($operators);
        $useCase->execute(new CreateOperatorInput('dup@example.com', 'firstpassword123'));

        $this->expectException(OperatorEmailConflictException::class);

        $useCase->execute(new CreateOperatorInput('dup@example.com', 'secondpassword123'));
    }

    public function testThrowsValidationForInvalidEmail(): void
    {
        $this->expectException(OperatorValidationException::class);

        $this->useCase()->execute(new CreateOperatorInput('not-an-email', 'validpassword123'));
    }

    public function testThrowsValidationForShortPassword(): void
    {
        $this->expectException(OperatorValidationException::class);

        $this->useCase()->execute(new CreateOperatorInput('op@example.com', 'short'));
    }

    public function testCreatesWithoutDisplayName(): void
    {
        $operators = new InMemoryOperatorRepository();

        $output = $this->useCase($operators)
            ->execute(new CreateOperatorInput('anon@example.com', 'verylongpassword1'));

        self::assertNull($output->operator->displayName);
        self::assertNotNull($operators->findByEmail('anon@example.com'));
    }

    private function useCase(
        ?InMemoryOperatorRepository $operators = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): CreateOperatorUseCase {
        return new CreateOperatorUseCase(
            operators: $operators ?? new InMemoryOperatorRepository(),
            hasher: new PasswordHasher(),
            audit: $recorder ?? new RecordingSuiteAuditRecorder(),
            suiteId: self::SUITE_ID,
        );
    }
}

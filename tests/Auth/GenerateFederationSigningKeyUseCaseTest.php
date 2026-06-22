<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\ActiveFederationSigningKeyExistsException;
use NeNeSuite\Auth\FederationKeyGenerator;
use NeNeSuite\Auth\FederationSigningKey;
use NeNeSuite\Auth\FederationSigningKeyStatus;
use NeNeSuite\Auth\GenerateFederationSigningKeyInput;
use NeNeSuite\Auth\GenerateFederationSigningKeyUseCase;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class GenerateFederationSigningKeyUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const NOW = '2026-06-22T00:00:00Z';

    public function testGeneratesActiveKeyAndAuditsGeneration(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($repository, $recorder)->execute(new GenerateFederationSigningKeyInput());

        self::assertNotSame('', $output->kid);
        self::assertStringContainsString('PRIVATE KEY', $output->privateKeyPem);

        $active = $repository->findActive();
        self::assertNotNull($active);
        self::assertSame($output->kid, $active->kid);
        self::assertSame('ES256', $active->alg);
        self::assertSame(FederationSigningKeyStatus::Active, $active->status);
        self::assertStringNotContainsString('PRIVATE', $active->publicJwk);

        self::assertCount(1, $recorder->commands);
        $command = $recorder->commands[0];
        self::assertSame('federation_signing_key.generated', $command->action);
        self::assertSame('federation_signing_key', $command->entityType);
        self::assertNull($command->beforeJson);
        self::assertIsArray($command->afterJson);
        self::assertSame($output->kid, $command->afterJson['kid']);
        self::assertSame('active', $command->afterJson['status']);
    }

    public function testRefusesWhenAnActiveKeyAlreadyExists(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save(new FederationSigningKey(
            '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            'kid-existing',
            'ES256',
            '{}',
            FederationSigningKeyStatus::Active,
            self::NOW,
            self::NOW,
            null,
        ));

        $this->expectException(ActiveFederationSigningKeyExistsException::class);

        $this->useCase($repository, new RecordingSuiteAuditRecorder())->execute(new GenerateFederationSigningKeyInput());
    }

    private function useCase(
        InMemoryFederationSigningKeyRepository $repository,
        RecordingSuiteAuditRecorder $recorder,
    ): GenerateFederationSigningKeyUseCase {
        return new GenerateFederationSigningKeyUseCase(
            new FederationKeyGenerator(),
            new ImmediateTransactionManager(),
            new InMemoryFederationSigningKeyRepositoryFactory($repository),
            new RecordingSuiteAuditRecorderFactory($recorder),
            self::SUITE_ID,
        );
    }
}

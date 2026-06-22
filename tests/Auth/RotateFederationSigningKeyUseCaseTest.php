<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\FederationKeyGenerator;
use NeNeSuite\Auth\FederationSigningKey;
use NeNeSuite\Auth\FederationSigningKeyStatus;
use NeNeSuite\Auth\GenerateFederationSigningKeyInput;
use NeNeSuite\Auth\NoActiveFederationSigningKeyException;
use NeNeSuite\Auth\RotateFederationSigningKeyUseCase;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class RotateFederationSigningKeyUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const NOW = '2026-06-22T00:00:00Z';

    public function testRotatesActiveToRetiringAndMintsNewActive(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $previous = (new FederationKeyGenerator())->generate();
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TC', $previous->kid, FederationSigningKeyStatus::Active));
        $recorder = new RecordingSuiteAuditRecorder();

        $output = $this->useCase($repository, $recorder)->execute(new GenerateFederationSigningKeyInput());

        $active = $repository->findActive();
        self::assertNotNull($active);
        self::assertSame($output->kid, $active->kid);
        self::assertNotSame($previous->kid, $active->kid);
        self::assertStringContainsString('PRIVATE KEY', $output->privateKeyPem);

        // The previous active is now retiring and still published alongside the new active.
        $old = $repository->findByKid($previous->kid);
        self::assertNotNull($old);
        self::assertSame(FederationSigningKeyStatus::Retiring, $old->status);
        $publishable = array_map(static fn (FederationSigningKey $k): string => $k->kid, $repository->findPublishable());
        self::assertContains($previous->kid, $publishable);
        self::assertContains($active->kid, $publishable);

        self::assertCount(1, $recorder->commands);
        self::assertSame('federation_signing_key.rotated', $recorder->commands[0]->action);
        self::assertSame($previous->kid, $recorder->commands[0]->beforeJson['kid'] ?? null);
        self::assertSame($active->kid, $recorder->commands[0]->afterJson['kid'] ?? null);
    }

    public function testRetiresAPreviouslyRetiringKey(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TC', 'kid-old-retiring', FederationSigningKeyStatus::Retiring));
        $active = (new FederationKeyGenerator())->generate();
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TD', $active->kid, FederationSigningKeyStatus::Active));

        $this->useCase($repository, new RecordingSuiteAuditRecorder())->execute(new GenerateFederationSigningKeyInput());

        $oldRetiring = $repository->findByKid('kid-old-retiring');
        self::assertNotNull($oldRetiring);
        self::assertSame(FederationSigningKeyStatus::Retired, $oldRetiring->status);
        // Published set stays bounded: new active + the just-demoted previous active (2), not the old retiring.
        self::assertCount(2, $repository->findPublishable());
    }

    public function testThrowsWhenNoActiveKey(): void
    {
        $this->expectException(NoActiveFederationSigningKeyException::class);

        $this->useCase(new InMemoryFederationSigningKeyRepository(), new RecordingSuiteAuditRecorder())
            ->execute(new GenerateFederationSigningKeyInput());
    }

    private function useCase(
        InMemoryFederationSigningKeyRepository $repository,
        RecordingSuiteAuditRecorder $recorder,
    ): RotateFederationSigningKeyUseCase {
        return new RotateFederationSigningKeyUseCase(
            new FederationKeyGenerator(),
            new ImmediateTransactionManager(),
            new InMemoryFederationSigningKeyRepositoryFactory($repository),
            new RecordingSuiteAuditRecorderFactory($recorder),
            self::SUITE_ID,
        );
    }

    private function key(string $id, string $kid, FederationSigningKeyStatus $status): FederationSigningKey
    {
        return new FederationSigningKey($id, $kid, 'ES256', '{"kid":"' . $kid . '"}', $status, self::NOW, self::NOW, null);
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\FederationSigningKey;
use NeNeSuite\Auth\FederationSigningKeyNotFoundException;
use NeNeSuite\Auth\FederationSigningKeyStatus;
use NeNeSuite\Auth\RevokeFederationSigningKeyInput;
use NeNeSuite\Auth\RevokeFederationSigningKeyUseCase;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class RevokeFederationSigningKeyUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const NOW = '2026-06-22T00:00:00Z';

    public function testRevokesKeyAndDropsItFromPublishable(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TC', 'kid-active', FederationSigningKeyStatus::Active));
        $recorder = new RecordingSuiteAuditRecorder();

        $revokedNow = $this->useCase($repository, $recorder)->execute(new RevokeFederationSigningKeyInput('kid-active'));

        self::assertTrue($revokedNow);
        $key = $repository->findByKid('kid-active');
        self::assertNotNull($key);
        self::assertSame(FederationSigningKeyStatus::Revoked, $key->status);
        self::assertSame([], $repository->findPublishable());

        self::assertCount(1, $recorder->commands);
        self::assertSame('federation_signing_key.revoked', $recorder->commands[0]->action);
        self::assertSame('kid-active', $recorder->commands[0]->afterJson['kid'] ?? null);
        self::assertSame('revoked', $recorder->commands[0]->afterJson['status'] ?? null);
    }

    public function testThrowsForUnknownKid(): void
    {
        $this->expectException(FederationSigningKeyNotFoundException::class);

        $this->useCase(new InMemoryFederationSigningKeyRepository(), new RecordingSuiteAuditRecorder())
            ->execute(new RevokeFederationSigningKeyInput('01J0NOTAKEY00000000000000A'));
    }

    public function testAlreadyRevokedIsIdempotentNoOp(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save($this->key('01J8XR4ZS6Q9V2H7K3N5M0B8TC', 'kid-gone', FederationSigningKeyStatus::Revoked));
        $recorder = new RecordingSuiteAuditRecorder();

        $revokedNow = $this->useCase($repository, $recorder)->execute(new RevokeFederationSigningKeyInput('kid-gone'));

        self::assertFalse($revokedNow);
        self::assertCount(0, $recorder->commands, 'a no-op revoke must not write a second audit row');
    }

    private function useCase(
        InMemoryFederationSigningKeyRepository $repository,
        RecordingSuiteAuditRecorder $recorder,
    ): RevokeFederationSigningKeyUseCase {
        return new RevokeFederationSigningKeyUseCase(
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

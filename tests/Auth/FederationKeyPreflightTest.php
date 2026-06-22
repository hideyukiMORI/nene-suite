<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\FederationKeyGenerator;
use NeNeSuite\Auth\FederationKeyPreflight;
use NeNeSuite\Auth\FederationKeyPreflightException;
use NeNeSuite\Auth\FederationSigningKey;
use NeNeSuite\Auth\FederationSigningKeyStatus;
use PHPUnit\Framework\TestCase;

final class FederationKeyPreflightTest extends TestCase
{
    private const NOW = '2026-06-22T00:00:00Z';

    public function testPassesWhenPrivateKeyMatchesActiveKid(): void
    {
        $generated = (new FederationKeyGenerator())->generate();
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save($this->activeKey($generated->kid));

        $this->preflight($repository)->verify($generated->privateKeyPem);

        $this->expectNotToPerformAssertions();
    }

    public function testThrowsWhenPrivateKeyMissing(): void
    {
        $this->expectException(FederationKeyPreflightException::class);

        $this->preflight(new InMemoryFederationSigningKeyRepository())->verify('   ');
    }

    public function testThrowsWhenPrivateKeyUnloadable(): void
    {
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save($this->activeKey('01J0SOMEKID00000000000000A'));

        $this->expectException(FederationKeyPreflightException::class);
        $this->preflight($repository)->verify('not-a-valid-pem');
    }

    public function testThrowsWhenNoActiveKey(): void
    {
        $generated = (new FederationKeyGenerator())->generate();

        $this->expectException(FederationKeyPreflightException::class);
        $this->preflight(new InMemoryFederationSigningKeyRepository())->verify($generated->privateKeyPem);
    }

    public function testThrowsWhenKidDoesNotMatchActiveKey(): void
    {
        $generated = (new FederationKeyGenerator())->generate();
        $repository = new InMemoryFederationSigningKeyRepository();
        $repository->save($this->activeKey('01J0DIFFERENTKID000000000A'));

        $this->expectException(FederationKeyPreflightException::class);
        $this->preflight($repository)->verify($generated->privateKeyPem);
    }

    private function preflight(InMemoryFederationSigningKeyRepository $repository): FederationKeyPreflight
    {
        return new FederationKeyPreflight($repository, new FederationKeyGenerator());
    }

    private function activeKey(string $kid): FederationSigningKey
    {
        return new FederationSigningKey(
            '01J8XR4ZS6Q9V2H7K3N5M0B8TC',
            $kid,
            'ES256',
            '{"kid":"' . $kid . '"}',
            FederationSigningKeyStatus::Active,
            self::NOW,
            self::NOW,
            null,
        );
    }
}

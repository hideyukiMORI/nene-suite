<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use DateTimeImmutable;
use NeNeSuite\Origin\Base64Url;
use NeNeSuite\Origin\OriginPublicKey;
use NeNeSuite\Origin\OriginVerificationException;
use NeNeSuite\Origin\OriginVerificationFailure;
use PHPUnit\Framework\TestCase;

final class OriginPublicKeyTest extends TestCase
{
    public function testFromJwkParsesEd25519Members(): void
    {
        $key = OriginPublicKey::fromJwk([
            'kid' => 'k1',
            'kty' => 'OKP',
            'alg' => 'EdDSA',
            'crv' => 'Ed25519',
            'x' => Base64Url::encode(str_repeat("\x01", 32)),
            'status' => 'active',
            'not_after' => '2030-01-01T00:00:00Z',
        ]);

        self::assertSame('k1', $key->kid);
        self::assertSame('OKP', $key->kty);
        self::assertSame('EdDSA', $key->alg);
        self::assertInstanceOf(DateTimeImmutable::class, $key->notAfter);
    }

    public function testFromJwkRejectsMissingRequiredMember(): void
    {
        try {
            OriginPublicKey::fromJwk(['kty' => 'OKP', 'status' => 'active']);
            self::fail('expected malformed key');
        } catch (OriginVerificationException $exception) {
            self::assertSame(OriginVerificationFailure::MalformedKey, $exception->failure);
        }
    }

    public function testIsValidAtRespectsNotAfter(): void
    {
        $key = OriginPublicKey::fromJwk([
            'kid' => 'k1',
            'kty' => 'OKP',
            'status' => 'retiring',
            'not_after' => '2026-01-01T00:00:00Z',
        ]);

        self::assertTrue($key->isValidAt(new DateTimeImmutable('2025-12-31T23:59:59Z')));
        self::assertFalse($key->isValidAt(new DateTimeImmutable('2026-02-01T00:00:00Z')));
    }

    public function testIsValidAtIsPerpetualWithoutNotAfter(): void
    {
        $key = OriginPublicKey::fromJwk(['kid' => 'k1', 'kty' => 'OKP', 'status' => 'active']);

        self::assertTrue($key->isValidAt(new DateTimeImmutable('2099-01-01T00:00:00Z')));
    }

    public function testEd25519PublicKeyReturns32Bytes(): void
    {
        $key = OriginPublicKey::fromJwk([
            'kid' => 'k1',
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => Base64Url::encode(str_repeat("\x02", 32)),
            'status' => 'active',
        ]);

        self::assertSame(32, strlen($key->ed25519PublicKey()));
    }

    public function testEd25519PublicKeyRejectsNonOkpKey(): void
    {
        $key = OriginPublicKey::fromJwk(['kid' => 'k1', 'kty' => 'RSA', 'status' => 'active']);

        $this->assertMalformedKey(fn () => $key->ed25519PublicKey());
    }

    public function testEd25519PublicKeyRejectsWrongLength(): void
    {
        $key = OriginPublicKey::fromJwk([
            'kid' => 'k1',
            'kty' => 'OKP',
            'crv' => 'Ed25519',
            'x' => Base64Url::encode('too-short'),
            'status' => 'active',
        ]);

        $this->assertMalformedKey(fn () => $key->ed25519PublicKey());
    }

    private function assertMalformedKey(callable $act): void
    {
        try {
            $act();
            self::fail('expected malformed key');
        } catch (OriginVerificationException $exception) {
            self::assertSame(OriginVerificationFailure::MalformedKey, $exception->failure);
        }
    }
}

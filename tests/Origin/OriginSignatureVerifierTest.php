<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\Base64Url;
use NeNeSuite\Origin\DetachedJws;
use NeNeSuite\Origin\OriginPublicKey;
use NeNeSuite\Origin\OriginSignatureVerifier;
use NeNeSuite\Origin\OriginVerificationException;
use NeNeSuite\Origin\OriginVerificationFailure;
use PHPUnit\Framework\TestCase;

final class OriginSignatureVerifierTest extends TestCase
{
    /** @var array{alg: string, kid: string, b64: bool, crit: list<string>} */
    private const HEADER = ['alg' => 'EdDSA', 'kid' => 'k1', 'b64' => false, 'crit' => ['b64']];

    /** @var non-empty-string */
    private string $secret;

    private OriginPublicKey $key;

    protected function setUp(): void
    {
        parent::setUp();
        $keypair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($keypair);
        $this->key = self::keyFrom(sodium_crypto_sign_publickey($keypair));
    }

    public function testVerifiesValidEd25519Signature(): void
    {
        $body = '{"gen":3}';
        $jws = DetachedJws::parse($this->sign($body, self::HEADER));

        (new OriginSignatureVerifier())->verify($jws, $body, $this->key);

        self::assertSame('k1', $jws->kid);
        self::assertSame('EdDSA', $jws->alg);
    }

    public function testRejectsTamperedBody(): void
    {
        $jws = DetachedJws::parse($this->sign('{"gen":3}', self::HEADER));

        $this->assertFailure(
            OriginVerificationFailure::SignatureInvalid,
            fn () => (new OriginSignatureVerifier())->verify($jws, '{"gen":4}', $this->key),
        );
    }

    public function testRejectsSignatureFromAnotherKey(): void
    {
        $body = '{"gen":3}';
        $jws = DetachedJws::parse($this->sign($body, self::HEADER));
        $otherKey = self::keyFrom(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));

        $this->assertFailure(
            OriginVerificationFailure::SignatureInvalid,
            fn () => (new OriginSignatureVerifier())->verify($jws, $body, $otherKey),
        );
    }

    public function testRejectsDisallowedAlgorithmBeforeCrypto(): void
    {
        $body = '{}';
        $jws = DetachedJws::parse($this->sign($body, ['alg' => 'none', 'kid' => 'k1', 'b64' => false, 'crit' => ['b64']]));

        $this->assertFailure(
            OriginVerificationFailure::AlgorithmDisallowed,
            fn () => (new OriginSignatureVerifier())->verify($jws, $body, $this->key),
        );
    }

    public function testDefersAllowlistedButUnimplementedAlgorithm(): void
    {
        $body = '{}';
        $jws = DetachedJws::parse($this->sign($body, ['alg' => 'ES256', 'kid' => 'k1', 'b64' => false, 'crit' => ['b64']]));
        // A matching ES256 key (so the not-implemented check is reached, not key-alg-mismatch).
        $ecKey = new OriginPublicKey(
            kid: 'k1',
            kty: 'EC',
            alg: 'ES256',
            crv: 'P-256',
            x: null,
            y: null,
            n: null,
            e: null,
            status: 'active',
            notAfter: null,
        );

        $this->assertFailure(
            OriginVerificationFailure::AlgorithmNotImplemented,
            fn () => (new OriginSignatureVerifier())->verify($jws, $body, $ecKey),
        );
    }

    public function testRejectsKeyAlgorithmMismatch(): void
    {
        $body = '{}';
        $jws = DetachedJws::parse($this->sign($body, self::HEADER));
        $rsaTaggedKey = new OriginPublicKey(
            kid: 'k1',
            kty: 'OKP',
            alg: 'RS256',
            crv: 'Ed25519',
            x: $this->key->x,
            y: null,
            n: null,
            e: null,
            status: 'active',
            notAfter: null,
        );

        $this->assertFailure(
            OriginVerificationFailure::KeyAlgorithmMismatch,
            fn () => (new OriginSignatureVerifier())->verify($jws, $body, $rsaTaggedKey),
        );
    }

    private static function keyFrom(string $publicKey): OriginPublicKey
    {
        return new OriginPublicKey(
            kid: 'k1',
            kty: 'OKP',
            alg: 'EdDSA',
            crv: 'Ed25519',
            x: Base64Url::encode($publicKey),
            y: null,
            n: null,
            e: null,
            status: 'active',
            notAfter: null,
        );
    }

    /**
     * @param array<string, mixed> $header
     */
    private function sign(string $body, array $header): string
    {
        $protectedB64 = Base64Url::encode(json_encode($header, JSON_THROW_ON_ERROR));
        $signature = sodium_crypto_sign_detached($protectedB64 . '.' . $body, $this->secret);

        return $protectedB64 . '..' . Base64Url::encode($signature);
    }

    private function assertFailure(OriginVerificationFailure $expected, callable $act): void
    {
        try {
            $act();
            self::fail(sprintf('expected %s', $expected->value));
        } catch (OriginVerificationException $exception) {
            self::assertSame($expected, $exception->failure);
        }
    }
}

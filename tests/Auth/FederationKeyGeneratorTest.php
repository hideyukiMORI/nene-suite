<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\FederationKeyGenerator;
use NeNeSuite\Auth\JwkThumbprint;
use PHPUnit\Framework\TestCase;

final class FederationKeyGeneratorTest extends TestCase
{
    public function testGeneratesEs256KeyWithPublicJwkAndThumbprintKid(): void
    {
        $generated = (new FederationKeyGenerator())->generate();

        self::assertSame('ES256', $generated->alg);
        self::assertStringContainsString('PRIVATE KEY', $generated->privateKeyPem);

        $jwk = json_decode($generated->publicJwk, true);
        self::assertIsArray($jwk);
        self::assertSame('EC', $jwk['kty']);
        self::assertSame('P-256', $jwk['crv']);
        self::assertSame('sig', $jwk['use']);
        self::assertSame('ES256', $jwk['alg']);
        self::assertSame($generated->kid, $jwk['kid']);

        // The kid is the RFC 7638 thumbprint of the published x/y.
        self::assertSame(JwkThumbprint::computeEc('P-256', (string) $jwk['x'], (string) $jwk['y']), $generated->kid);

        // The stored public JWK must not contain private material.
        self::assertStringNotContainsString('PRIVATE', $generated->publicJwk);
        self::assertStringNotContainsString('"d"', $generated->publicJwk);
    }

    public function testEachGenerationProducesADistinctKey(): void
    {
        $generator = new FederationKeyGenerator();

        self::assertNotSame($generator->generate()->kid, $generator->generate()->kid);
    }

    public function testKidForPrivateKeyMatchesTheGeneratedKid(): void
    {
        $generator = new FederationKeyGenerator();
        $generated = $generator->generate();

        self::assertSame($generated->kid, $generator->kidForPrivateKey($generated->privateKeyPem));
    }

    public function testCoordinatesAreZeroPaddedToFieldSize(): void
    {
        $generator = new FederationKeyGenerator();

        // openssl drops leading-zero bytes (~1/256 per coordinate); a few iterations exercise the
        // left-padding so a short coordinate cannot yield a non-interoperable kid (RFC 7518 §6.2.1.2).
        for ($i = 0; $i < 16; $i++) {
            $jwk = json_decode($generator->generate()->publicJwk, true);
            self::assertIsArray($jwk);
            self::assertSame(32, strlen($this->base64UrlDecode((string) $jwk['x'])), 'x must be the full 32-byte field size');
            self::assertSame(32, strlen($this->base64UrlDecode((string) $jwk['y'])), 'y must be the full 32-byte field size');
        }
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder !== 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}

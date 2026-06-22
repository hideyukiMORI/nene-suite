<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use Firebase\JWT\JWT;
use Nene2\Auth\TokenVerificationException;
use NeNeSuite\Auth\AssertionTokenIssuer;
use NeNeSuite\Auth\AssertionTokenVerifier;
use PHPUnit\Framework\TestCase;

final class AssertionTokenVerifierTest extends TestCase
{
    private const KID = '01J0KID000000000000000000A';

    public function testRoundTripVerifiesIssuedClaims(): void
    {
        [$private, $public] = $this->keypair();
        $token = (new AssertionTokenIssuer($private, self::KID))->issue([
            'sub' => 'op-1',
            'org_external_id' => 'org-1',
            'exp' => time() + 60,
        ]);

        $claims = (new AssertionTokenVerifier([self::KID => $public]))->verify($token);

        self::assertSame('op-1', $claims['sub']);
        self::assertSame('org-1', $claims['org_external_id']);
    }

    public function testRejectsTokenSignedByAnotherKey(): void
    {
        [$privateA] = $this->keypair();
        [, $publicB] = $this->keypair();
        $token = (new AssertionTokenIssuer($privateA, self::KID))->issue(['sub' => 'x', 'exp' => time() + 60]);

        $this->expectException(TokenVerificationException::class);
        (new AssertionTokenVerifier([self::KID => $publicB]))->verify($token);
    }

    public function testRejectsUnknownKid(): void
    {
        [$private, $public] = $this->keypair();
        $token = (new AssertionTokenIssuer($private, '01J0OTHERKID0000000000000A'))->issue(['sub' => 'x', 'exp' => time() + 60]);

        $this->expectException(TokenVerificationException::class);
        (new AssertionTokenVerifier([self::KID => $public]))->verify($token);
    }

    public function testRejectsAlgNone(): void
    {
        [, $public] = $this->keypair();
        $token = $this->b64url('{"typ":"JWT","alg":"none","kid":"' . self::KID . '"}')
            . '.' . $this->b64url('{"sub":"attacker"}') . '.';

        $this->expectException(TokenVerificationException::class);
        (new AssertionTokenVerifier([self::KID => $public]))->verify($token);
    }

    public function testRejectsHs256AlgConfusion(): void
    {
        [, $public] = $this->keypair();
        // Classic attack: forge an HS256 token using the ES256 public key as the HMAC secret.
        $forged = JWT::encode(['sub' => 'attacker', 'exp' => time() + 60], $public, 'HS256', self::KID);

        $this->expectException(TokenVerificationException::class);
        (new AssertionTokenVerifier([self::KID => $public]))->verify($forged);
    }

    public function testRejectsTamperedPayload(): void
    {
        [$private, $public] = $this->keypair();
        $token = (new AssertionTokenIssuer($private, self::KID))->issue(['sub' => 'x', 'exp' => time() + 60]);
        [$header, , $signature] = explode('.', $token);
        $tampered = $header . '.' . $this->b64url('{"sub":"admin","exp":' . (time() + 60) . '}') . '.' . $signature;

        $this->expectException(TokenVerificationException::class);
        (new AssertionTokenVerifier([self::KID => $public]))->verify($tampered);
    }

    public function testRejectsExpiredToken(): void
    {
        [$private, $public] = $this->keypair();
        $token = (new AssertionTokenIssuer($private, self::KID))->issue(['sub' => 'x', 'exp' => time() - 10]);

        $this->expectException(TokenVerificationException::class);
        (new AssertionTokenVerifier([self::KID => $public]))->verify($token);
    }

    public function testRejectsWhenNoKeysConfigured(): void
    {
        [$private] = $this->keypair();
        $token = (new AssertionTokenIssuer($private, self::KID))->issue(['sub' => 'x', 'exp' => time() + 60]);

        $this->expectException(TokenVerificationException::class);
        (new AssertionTokenVerifier([]))->verify($token);
    }

    /**
     * @return array{0: string, 1: string} [privatePem, publicPem]
     */
    private function keypair(): array
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        self::assertNotFalse($resource, 'EC key generation requires ext-openssl with P-256 support');
        openssl_pkey_export($resource, $privatePem);
        $details = openssl_pkey_get_details($resource);
        self::assertIsArray($details);

        return [(string) $privatePem, (string) $details['key']];
    }

    private function b64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

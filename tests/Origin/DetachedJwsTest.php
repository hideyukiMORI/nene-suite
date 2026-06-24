<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\Base64Url;
use NeNeSuite\Origin\DetachedJws;
use NeNeSuite\Origin\OriginVerificationException;
use NeNeSuite\Origin\OriginVerificationFailure;
use PHPUnit\Framework\TestCase;

final class DetachedJwsTest extends TestCase
{
    public function testParsesValidDetachedHeader(): void
    {
        $jws = DetachedJws::parse(self::compact(['alg' => 'EdDSA', 'kid' => 'k9', 'b64' => false, 'crit' => ['b64']]));

        self::assertSame('EdDSA', $jws->alg);
        self::assertSame('k9', $jws->kid);
        self::assertNotSame('', $jws->protectedB64);
    }

    public function testRejectsNonDetachedForm(): void
    {
        // A standard JWS with a non-empty payload segment is not the detached form.
        $this->assertFailure(OriginVerificationFailure::MalformedJws, 'aaa.bbb.ccc');
    }

    public function testRejectsEncodedPayload(): void
    {
        $this->assertFailure(
            OriginVerificationFailure::UnencodedPayloadRequired,
            self::compact(['alg' => 'EdDSA', 'kid' => 'k1', 'b64' => true, 'crit' => ['b64']]),
        );
    }

    public function testRejectsMissingCritB64(): void
    {
        $this->assertFailure(
            OriginVerificationFailure::MalformedJws,
            self::compact(['alg' => 'EdDSA', 'kid' => 'k1', 'b64' => false]),
        );
    }

    public function testRejectsMissingAlg(): void
    {
        $this->assertFailure(
            OriginVerificationFailure::MalformedJws,
            self::compact(['kid' => 'k1', 'b64' => false, 'crit' => ['b64']]),
        );
    }

    /**
     * @param array<string, mixed> $header
     */
    private static function compact(array $header): string
    {
        return Base64Url::encode(json_encode($header, JSON_THROW_ON_ERROR)) . '..' . Base64Url::encode('sig');
    }

    private function assertFailure(OriginVerificationFailure $expected, string $compact): void
    {
        try {
            DetachedJws::parse($compact);
            self::fail(sprintf('expected %s', $expected->value));
        } catch (OriginVerificationException $exception) {
            self::assertSame($expected, $exception->failure);
        }
    }
}

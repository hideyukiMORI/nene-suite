<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\AssertionTokenIssuer;
use PHPUnit\Framework\TestCase;

final class AssertionTokenIssuerTest extends TestCase
{
    public function testHeaderCarriesEs256AndKid(): void
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        self::assertNotFalse($resource);
        openssl_pkey_export($resource, $privatePem);

        $token = (new AssertionTokenIssuer((string) $privatePem, '01J0KID000000000000000000A'))
            ->issue(['sub' => 'op-1', 'exp' => time() + 60]);

        [$headerSegment] = explode('.', $token);
        $header = json_decode((string) base64_decode(strtr($headerSegment, '-_', '+/'), true), true);
        self::assertIsArray($header);
        self::assertSame('ES256', $header['alg']);
        self::assertSame('01J0KID000000000000000000A', $header['kid']);
        self::assertSame('JWT', $header['typ']);
    }
}

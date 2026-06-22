<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\JwkThumbprint;
use PHPUnit\Framework\TestCase;

final class JwkThumbprintTest extends TestCase
{
    public function testIsDeterministicAndBase64Url(): void
    {
        $first = JwkThumbprint::computeEc('P-256', 'abc-XYZ_123', 'def-UVW_456');
        $second = JwkThumbprint::computeEc('P-256', 'abc-XYZ_123', 'def-UVW_456');

        self::assertSame($first, $second);
        self::assertSame(43, strlen($first)); // SHA-256 → 32 bytes → 43 base64url chars (no padding)
        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]+$/', $first);
    }

    public function testMatchesIndependentRfc7638Canonicalization(): void
    {
        $x = 'abc-XYZ_123';
        $y = 'def-UVW_456';
        // RFC 7638 §3.2: required members only, lexicographic order, no whitespace.
        $canonical = '{"crv":"P-256","kty":"EC","x":"' . $x . '","y":"' . $y . '"}';
        $expected = rtrim(strtr(base64_encode(hash('sha256', $canonical, true)), '+/', '-_'), '=');

        self::assertSame($expected, JwkThumbprint::computeEc('P-256', $x, $y));
    }

    public function testDifferentCoordinatesProduceDifferentThumbprints(): void
    {
        self::assertNotSame(
            JwkThumbprint::computeEc('P-256', 'x1', 'y1'),
            JwkThumbprint::computeEc('P-256', 'x2', 'y1'),
        );
    }
}

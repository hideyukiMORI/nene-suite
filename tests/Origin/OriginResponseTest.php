<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\OriginResponse;
use PHPUnit\Framework\TestCase;

final class OriginResponseTest extends TestCase
{
    public function testOkResponseExposesRawBodyAndEtag(): void
    {
        $response = new OriginResponse(200, '{"gen":3}', '"e1"');

        self::assertTrue($response->isOk());
        self::assertFalse($response->isNotModified());
        self::assertSame('{"gen":3}', $response->body);
        self::assertSame('"e1"', $response->etag);
    }

    public function testNotModifiedResponse(): void
    {
        $response = new OriginResponse(304, '', '"e1"');

        self::assertTrue($response->isNotModified());
        self::assertFalse($response->isOk());
    }
}

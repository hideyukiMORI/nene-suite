<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\StreamOriginHttpClient;
use PHPUnit\Framework\TestCase;

final class StreamOriginHttpClientTest extends TestCase
{
    public function testParsesStatusAndEtag(): void
    {
        [$status, $etag] = StreamOriginHttpClient::parseHead([
            'HTTP/1.1 200 OK',
            'Content-Type: application/json',
            'ETag: "abc123"',
        ]);

        self::assertSame(200, $status);
        self::assertSame('"abc123"', $etag);
    }

    public function testLastStatusLineWinsAndResetsEtag(): void
    {
        [$status, $etag] = StreamOriginHttpClient::parseHead([
            'HTTP/1.1 301 Moved Permanently',
            'ETag: "stale"',
            'Location: /elsewhere',
            'HTTP/1.1 200 OK',
            'Content-Type: application/json',
        ]);

        self::assertSame(200, $status);
        self::assertNull($etag);
    }

    public function testParsesNotModified(): void
    {
        [$status, $etag] = StreamOriginHttpClient::parseHead(['HTTP/2 304']);

        self::assertSame(304, $status);
        self::assertNull($etag);
    }

    public function testEmptyHeadersYieldZeroStatus(): void
    {
        [$status, $etag] = StreamOriginHttpClient::parseHead([]);

        self::assertSame(0, $status);
        self::assertNull($etag);
    }
}

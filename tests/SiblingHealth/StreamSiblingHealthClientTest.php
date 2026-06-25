<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\SiblingHealth;

use NeNeSuite\SiblingHealth\StreamSiblingHealthClient;
use PHPUnit\Framework\TestCase;

final class StreamSiblingHealthClientTest extends TestCase
{
    public function testParsesVersionFromHealthBody(): void
    {
        self::assertSame('1.4.2', StreamSiblingHealthClient::parseVersion('{"status":"ok","version":"1.4.2"}'));
    }

    public function testNullWhenVersionFieldMissing(): void
    {
        self::assertNull(StreamSiblingHealthClient::parseVersion('{"status":"ok","time":"2026-06-25T00:00:00Z"}'));
    }

    public function testNullWhenVersionBlank(): void
    {
        self::assertNull(StreamSiblingHealthClient::parseVersion('{"version":""}'));
    }

    public function testNullWhenVersionNotString(): void
    {
        self::assertNull(StreamSiblingHealthClient::parseVersion('{"version":140}'));
    }

    public function testNullWhenBodyIsNotJson(): void
    {
        self::assertNull(StreamSiblingHealthClient::parseVersion('not json at all'));
    }

    public function testNullWhenBodyIsJsonArray(): void
    {
        self::assertNull(StreamSiblingHealthClient::parseVersion('["1.0.0"]'));
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\HttpOriginObjectStore;
use NeNeSuite\Origin\OriginUnreachableException;
use PHPUnit\Framework\TestCase;

final class HttpOriginObjectStoreTest extends TestCase
{
    private const string BASE = 'https://origin.example.com';

    public function testReadGetsBaseUrlPlusPathAndReturnsBody(): void
    {
        $client = new RecordingOriginHttpClient(200, 'ROOT-BYTES');
        $store = new HttpOriginObjectStore($client, self::BASE);

        self::assertSame('ROOT-BYTES', $store->read('v1/root.json'));
        self::assertSame([self::BASE . '/v1/root.json'], $client->urls);
    }

    public function testContentAddressedAndSidecarPaths(): void
    {
        $client = new RecordingOriginHttpClient();
        $store = new HttpOriginObjectStore($client, self::BASE);

        $store->readSidecar('v1/root.json');
        $store->readByHash('targets', 'abc');
        $store->readArtifact('def');

        self::assertSame([
            self::BASE . '/v1/root.json.jws',
            self::BASE . '/v1/targets/abc.json',
            self::BASE . '/v1/artifacts/def',
        ], $client->urls);
    }

    public function testNon200ThrowsUnreachable(): void
    {
        $store = new HttpOriginObjectStore(new RecordingOriginHttpClient(404), self::BASE);

        $this->expectException(OriginUnreachableException::class);
        $store->read('v1/nene-invoice/stable/current');
    }
}

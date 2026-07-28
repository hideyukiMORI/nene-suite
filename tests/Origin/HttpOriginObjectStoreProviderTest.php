<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\HttpOriginObjectStoreProvider;
use NeNeSuite\Origin\OriginMirrorList;
use NeNeSuite\Origin\OriginObjectStore;
use PHPUnit\Framework\TestCase;

final class HttpOriginObjectStoreProviderTest extends TestCase
{
    public function testYieldsOneStorePerMirrorInListOrderKeyedByBaseUrl(): void
    {
        $client = new RecordingOriginHttpClient();
        $provider = new HttpOriginObjectStoreProvider(
            $client,
            new OriginMirrorList(['https://a.example.com', 'https://b.example.com']),
        );

        $bases = [];
        foreach ($provider->stores() as $baseUrl => $store) {
            $bases[] = $baseUrl;
            $store->read('v1/root.json');
        }

        self::assertSame(['https://a.example.com', 'https://b.example.com'], $bases);
        self::assertSame([
            'https://a.example.com/v1/root.json',
            'https://b.example.com/v1/root.json',
        ], $client->urls);
    }

    public function testStoresAreLazySoAVerifiedFirstMirrorNeverTouchesTheSecond(): void
    {
        $client = new RecordingOriginHttpClient();
        $provider = new HttpOriginObjectStoreProvider(
            $client,
            new OriginMirrorList(['https://a.example.com', 'https://b.example.com']),
        );

        foreach ($provider->stores() as $store) {
            self::assertInstanceOf(OriginObjectStore::class, $store);
            $store->read('v1/root.json');

            break; // the caller's walk verified against the primary
        }

        self::assertSame(['https://a.example.com/v1/root.json'], $client->urls);
    }

    public function testAnEmptyMirrorListYieldsNothing(): void
    {
        $provider = new HttpOriginObjectStoreProvider(new RecordingOriginHttpClient(), OriginMirrorList::none());

        self::assertSame([], iterator_to_array($provider->stores()));
    }
}

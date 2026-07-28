<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The production {@see OriginObjectStoreProvider}: one {@see HttpOriginObjectStore} per mirror, in
 * {@see OriginMirrorList} order. Stores are built lazily, so a walk that verifies against the
 * primary never constructs (or touches) the secondary.
 */
final readonly class HttpOriginObjectStoreProvider implements OriginObjectStoreProvider
{
    public function __construct(
        private OriginHttpClientInterface $client,
        private OriginMirrorList $mirrors,
    ) {
    }

    public function stores(): iterable
    {
        foreach ($this->mirrors->baseUrls as $baseUrl) {
            yield $baseUrl => new HttpOriginObjectStore($this->client, $baseUrl);
        }
    }
}

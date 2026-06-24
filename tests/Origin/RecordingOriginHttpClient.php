<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Origin;

use NeNeSuite\Origin\OriginHttpClientInterface;
use NeNeSuite\Origin\OriginResponse;

/**
 * Test double for {@see OriginHttpClientInterface} that records the URLs it is asked to GET and
 * returns a canned response — lets the store be tested without a network.
 */
final class RecordingOriginHttpClient implements OriginHttpClientInterface
{
    /** @var list<string> */
    public array $urls = [];

    public function __construct(
        private readonly int $status = 200,
        private readonly string $body = 'OBJECT-BYTES',
    ) {
    }

    public function get(string $url, ?string $etag = null): OriginResponse
    {
        $this->urls[] = $url;

        return new OriginResponse($this->status, $this->body, null);
    }
}

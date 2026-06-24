<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Native PHP-streams Origin HTTP client (no new dependency, no ext-curl needed —
 * smallest runtime surface). Behind {@see OriginHttpClientInterface} so it can be
 * swapped without touching callers. Conditional GET via `If-None-Match`; non-2xx
 * responses are returned (not thrown) so callers can act on `304` / `404`.
 */
final readonly class StreamOriginHttpClient implements OriginHttpClientInterface
{
    public function __construct(private int $timeoutSeconds = 10)
    {
    }

    public function get(string $url, ?string $etag = null): OriginResponse
    {
        $header = 'Accept: application/json' . "\r\n";

        if ($etag !== null && $etag !== '') {
            $header .= 'If-None-Match: ' . $etag . "\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $header,
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);

        $stream = @fopen($url, 'rb', false, $context);

        if ($stream === false) {
            throw new OriginUnreachableException(sprintf('Origin request failed: %s', $url));
        }

        try {
            $meta = stream_get_meta_data($stream);
            $body = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }

        if (!is_string($body)) {
            throw new OriginUnreachableException(sprintf('Origin response unreadable: %s', $url));
        }

        $wrapperData = $meta['wrapper_data'] ?? [];
        $lines = is_array($wrapperData) ? array_values(array_filter($wrapperData, 'is_string')) : [];
        [$status, $responseEtag] = self::parseHead($lines);

        return new OriginResponse($status, $body, $responseEtag);
    }

    /**
     * Parse the HTTP status code and ETag from raw response header lines.
     * The last status line wins (redirect chains) and resets a stale ETag.
     *
     * @param list<string> $headerLines
     *
     * @return array{0: int, 1: ?string}
     */
    public static function parseHead(array $headerLines): array
    {
        $status = 0;
        $etag = null;

        foreach ($headerLines as $line) {
            if (preg_match('#^HTTP/\d(?:\.\d)?\s+(\d{3})#', $line, $matches) === 1) {
                $status = (int) $matches[1];
                $etag = null;
            } elseif (preg_match('#^ETag:\s*(.+?)\s*$#i', $line, $matches) === 1) {
                $etag = $matches[1];
            }
        }

        return [$status, $etag];
    }
}

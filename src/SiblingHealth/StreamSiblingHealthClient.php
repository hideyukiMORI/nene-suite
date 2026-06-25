<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

use JsonException;

/**
 * Native PHP-streams probe of `{publicUrl}/health` (no new dependency — mirrors
 * {@see \NeNeSuite\Origin\StreamOriginHttpClient}). Non-throwing by contract: any transport error,
 * non-200 status, non-JSON body, or missing / blank `version` field degrades to null. The Suite
 * reaches siblings over the internal network; service-token gating of the version is deferred to
 * the ADR 0013 §5 aggregation contract.
 */
final readonly class StreamSiblingHealthClient implements SiblingHealthClientInterface
{
    public function __construct(private int $timeoutSeconds = 5)
    {
    }

    public function fetchVersion(string $publicUrl): ?string
    {
        $url = rtrim($publicUrl, '/') . '/health';

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: application/json' . "\r\n",
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);

        $stream = @fopen($url, 'rb', false, $context);

        if ($stream === false) {
            return null;
        }

        try {
            $meta = stream_get_meta_data($stream);
            $body = stream_get_contents($stream);
        } finally {
            fclose($stream);
        }

        if (!is_string($body)) {
            return null;
        }

        $wrapperData = $meta['wrapper_data'] ?? [];
        $lines = is_array($wrapperData) ? array_values(array_filter($wrapperData, 'is_string')) : [];

        if (!self::isOk($lines)) {
            return null;
        }

        return self::parseVersion($body);
    }

    /**
     * True when the last HTTP status line is 200 (redirect chains: the last status wins).
     *
     * @param list<string> $headerLines
     */
    private static function isOk(array $headerLines): bool
    {
        $status = 0;

        foreach ($headerLines as $line) {
            if (preg_match('#^HTTP/\d(?:\.\d)?\s+(\d{3})#', $line, $matches) === 1) {
                $status = (int) $matches[1];
            }
        }

        return $status === 200;
    }

    /**
     * Extract a non-blank `version` string from a sibling `/health` JSON body, or null when the
     * body is not a JSON object or carries no usable `version` field.
     */
    public static function parseVersion(string $body): ?string
    {
        try {
            $decoded = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($decoded)) {
            return null;
        }

        $version = $decoded['version'] ?? null;

        return is_string($version) && $version !== '' ? $version : null;
    }
}

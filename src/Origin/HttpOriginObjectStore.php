<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Production {@see OriginObjectStore}: fetches the static `/v1` objects over HTTP via the O0 client.
 * Returns raw served bytes (the verifier checks integrity over them); any non-200 — including a
 * `404` for an absent product/channel — surfaces as {@see OriginUnreachableException}, which the
 * aggregator turns into an "unavailable" signal rather than failing the whole pass.
 */
final readonly class HttpOriginObjectStore implements OriginObjectStore
{
    public function __construct(
        private OriginHttpClientInterface $client,
        private string $baseUrl,
    ) {
    }

    public function read(string $relativePath): string
    {
        $response = $this->client->get($this->baseUrl . '/' . ltrim($relativePath, '/'));

        if (!$response->isOk()) {
            throw new OriginUnreachableException(sprintf('Origin returned HTTP %d for %s', $response->status, $relativePath));
        }

        return $response->body;
    }

    public function readSidecar(string $relativePath): string
    {
        return $this->read($relativePath . '.jws');
    }

    public function readByHash(string $kind, string $sha256): string
    {
        return $this->read(sprintf('v1/%s/%s.json', $kind, $sha256));
    }

    public function readSidecarByHash(string $kind, string $sha256): string
    {
        return $this->readSidecar(sprintf('v1/%s/%s.json', $kind, $sha256));
    }

    public function readArtifact(string $sha256): string
    {
        return $this->read(sprintf('v1/artifacts/%s', $sha256));
    }
}

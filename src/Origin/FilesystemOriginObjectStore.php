<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Reads object bytes from a directory tree mirroring the static `/v1` CDN layout (the conformance
 * corpus case directory). Returns **raw bytes** — never re-parsed or re-serialised — because
 * integrity is verified over the literal served bytes. Content-addressed objects live under their
 * role directory named by `sha256`; the mutable `current` and `root.json` sit at fixed paths.
 */
final readonly class FilesystemOriginObjectStore implements OriginObjectStore
{
    public function __construct(private string $baseDirectory)
    {
    }

    public function read(string $relativePath): string
    {
        $path = $this->baseDirectory . '/' . $relativePath;
        $bytes = @file_get_contents($path);

        if ($bytes === false) {
            throw new OriginUnreachableException(sprintf('Origin object not found: %s', $relativePath));
        }

        return $bytes;
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

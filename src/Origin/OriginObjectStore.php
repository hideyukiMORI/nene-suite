<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Reads raw Origin object bytes by their `/v1` path — the seam between the verifier (which checks
 * integrity over the **literal served bytes**, never re-parsed) and the source. The corpus harness
 * supplies a filesystem-backed store; the live fetch path (O3) supplies an HTTP-backed one.
 *
 * @throws OriginUnreachableException when an object cannot be read
 */
interface OriginObjectStore
{
    public function read(string $relativePath): string;

    public function readSidecar(string $relativePath): string;

    /** A content-addressed object, e.g. `kind=targets, sha=abc… → v1/targets/abc….json`. */
    public function readByHash(string $kind, string $sha256): string;

    public function readSidecarByHash(string $kind, string $sha256): string;

    /** The artifact binary is content-addressed with no extension and no sidecar. */
    public function readArtifact(string $sha256): string;
}

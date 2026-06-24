<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Outbound HTTP seam for the Origin read path (signed static GETs).
 *
 * Origin objects are immutable + content-addressed except `current`, so callers
 * pass the last-seen ETag for conditional requests (`If-None-Match` → `304`).
 * The body is returned as raw bytes — the profiled-TUF verifier (O1) checks the
 * detached `.jws` against the literal served bytes, never a re-serialization.
 */
interface OriginHttpClientInterface
{
    /**
     * GET an Origin object. `$etag` (when non-null) is sent as `If-None-Match`.
     *
     * @throws OriginUnreachableException when the request cannot be completed
     */
    public function get(string $url, ?string $etag = null): OriginResponse;
}

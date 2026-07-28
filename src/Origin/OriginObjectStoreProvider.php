<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Yields the object stores a single verification walk may be attempted against, in mirror order
 * (`nene-origin/docs/spec/mirrors.md` §4). Callers iterate lazily and **stop at the first fully
 * verified walk**; a walk MUST complete against one store (objects are never mixed across bases,
 * §4.1), which is why the seam yields stores rather than routing per read.
 */
interface OriginObjectStoreProvider
{
    /**
     * @return iterable<string, OriginObjectStore> keyed by the base URL (or label) of the attempt,
     *                                             used for per-mirror warning surfacing
     */
    public function stores(): iterable;
}

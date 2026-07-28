<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * A one-store {@see OriginObjectStoreProvider}: no failover, one attempt. Used wherever the source
 * is not a mirrored read path — the conformance corpus harness (filesystem store) and the
 * `NENE_ORIGIN_URL` exclusive override both reduce to this shape.
 */
final readonly class SingleOriginObjectStoreProvider implements OriginObjectStoreProvider
{
    public function __construct(
        private OriginObjectStore $store,
        private string $label = 'origin',
    ) {
    }

    public function stores(): iterable
    {
        yield $this->label => $this->store;
    }
}

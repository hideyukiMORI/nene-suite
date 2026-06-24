<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * One Origin HTTP response. `body` is the raw served bytes (the signature input
 * for the detached `.jws`; never re-canonicalize before verifying — ADR 0017 §3).
 */
final readonly class OriginResponse
{
    public function __construct(
        public int $status,
        public string $body,
        public ?string $etag,
    ) {
    }

    public function isOk(): bool
    {
        return $this->status === 200;
    }

    public function isNotModified(): bool
    {
        return $this->status === 304;
    }
}

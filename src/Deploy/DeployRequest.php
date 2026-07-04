<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

/**
 * One "recreate `service` at `imageDigest`" instruction for the host-side deploy agent
 * (ADR 0019 OQ1). `service` is a catalog app id (explicit allow-list), `imageDigest` an
 * immutable `sha256:` pin (OQ2 stage 1). Timestamps are ISO-8601 UTC strings.
 */
final readonly class DeployRequest
{
    public function __construct(
        public string $id,
        public string $service,
        public string $imageDigest,
        public DeployRequestStatus $status,
        public ?string $requestedBy,
        public ?string $detail,
        public string $createdAt,
        public string $updatedAt,
        public ?string $completedAt,
    ) {
    }
}

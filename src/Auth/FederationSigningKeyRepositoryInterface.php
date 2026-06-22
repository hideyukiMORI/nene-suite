<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface FederationSigningKeyRepositoryInterface
{
    public function save(FederationSigningKey $key): void;

    /** The single active signing key, or null when none has been generated yet. */
    public function findActive(): ?FederationSigningKey;

    /**
     * Keys that should appear in the published JWKS — `active` plus `retiring` (a rotated-out key
     * kept available until in-flight assertions expire). Never `retired` or `revoked`.
     *
     * @return list<FederationSigningKey>
     */
    public function findPublishable(): array;
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface FederationSigningKeyRepositoryInterface
{
    public function save(FederationSigningKey $key): void;

    /** The single active signing key, or null when none has been generated yet. */
    public function findActive(): ?FederationSigningKey;
}

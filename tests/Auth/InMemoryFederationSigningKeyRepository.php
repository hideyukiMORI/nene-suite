<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\FederationSigningKey;
use NeNeSuite\Auth\FederationSigningKeyRepositoryInterface;
use NeNeSuite\Auth\FederationSigningKeyStatus;

final class InMemoryFederationSigningKeyRepository implements FederationSigningKeyRepositoryInterface
{
    /** @var array<string, FederationSigningKey> */
    private array $byId = [];

    public function save(FederationSigningKey $key): void
    {
        $this->byId[$key->id] = $key;
    }

    public function findActive(): ?FederationSigningKey
    {
        foreach ($this->byId as $key) {
            if ($key->status === FederationSigningKeyStatus::Active) {
                return $key;
            }
        }

        return null;
    }
}

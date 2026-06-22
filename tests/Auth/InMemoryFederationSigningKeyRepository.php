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

    /**
     * @return list<FederationSigningKey>
     */
    public function findPublishable(): array
    {
        return array_values(array_filter(
            $this->byId,
            static fn (FederationSigningKey $key): bool => in_array(
                $key->status,
                [FederationSigningKeyStatus::Active, FederationSigningKeyStatus::Retiring],
                true,
            ),
        ));
    }

    public function findByKid(string $kid): ?FederationSigningKey
    {
        foreach ($this->byId as $key) {
            if ($key->kid === $kid) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @return list<FederationSigningKey>
     */
    public function findByStatus(FederationSigningKeyStatus $status): array
    {
        return array_values(array_filter(
            $this->byId,
            static fn (FederationSigningKey $key): bool => $key->status === $status,
        ));
    }

    public function updateStatus(string $id, FederationSigningKeyStatus $status, ?string $retiredAt): void
    {
        $existing = $this->byId[$id] ?? null;

        if ($existing === null) {
            return;
        }

        $this->byId[$id] = new FederationSigningKey(
            $existing->id,
            $existing->kid,
            $existing->alg,
            $existing->publicJwk,
            $status,
            $existing->createdAt,
            $existing->activatedAt,
            $retiredAt,
        );
    }
}

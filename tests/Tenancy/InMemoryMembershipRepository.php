<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Tenancy;

use NeNeSuite\Tenancy\Membership;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;

final class InMemoryMembershipRepository implements MembershipRepositoryInterface
{
    /** @var array<string, Membership> */
    private array $byId = [];

    public function save(Membership $membership): void
    {
        $this->byId[$membership->id] = $membership;
    }

    public function findById(string $id): ?Membership
    {
        return $this->byId[$id] ?? null;
    }

    /**
     * @return list<Membership>
     */
    public function findByOperator(string $operatorId): array
    {
        return array_values(array_filter(
            $this->byId,
            static fn (Membership $membership): bool => $membership->operatorId === $operatorId,
        ));
    }

    public function findByOperatorAndOrganization(string $operatorId, ?string $organizationId): ?Membership
    {
        foreach ($this->byId as $membership) {
            if ($membership->operatorId === $operatorId && $membership->organizationId === $organizationId) {
                return $membership;
            }
        }

        return null;
    }

    /**
     * @return list<Membership>
     */
    public function findByOrganization(string $organizationId): array
    {
        return array_values(array_filter(
            $this->byId,
            static fn (Membership $membership): bool => $membership->organizationId === $organizationId,
        ));
    }
}

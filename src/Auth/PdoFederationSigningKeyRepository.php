<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;

final readonly class PdoFederationSigningKeyRepository implements FederationSigningKeyRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function save(FederationSigningKey $key): void
    {
        $this->query->execute(
            <<<'SQL'
                INSERT INTO federation_signing_keys (id, kid, alg, public_jwk, status, created_at, activated_at, retired_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                SQL,
            [
                $key->id,
                $key->kid,
                $key->alg,
                $key->publicJwk,
                $key->status->value,
                $key->createdAt,
                $key->activatedAt,
                $key->retiredAt,
            ],
        );
    }

    public function findActive(): ?FederationSigningKey
    {
        return $this->hydrate($this->query->fetchOne(
            'SELECT * FROM federation_signing_keys WHERE status = ? LIMIT 1',
            [FederationSigningKeyStatus::Active->value],
        ));
    }

    /**
     * @param array<string, mixed>|null $row
     */
    private function hydrate(?array $row): ?FederationSigningKey
    {
        if ($row === null) {
            return null;
        }

        $activatedAt = $row['activated_at'] ?? null;
        $retiredAt = $row['retired_at'] ?? null;

        return new FederationSigningKey(
            id: (string) $row['id'],
            kid: (string) $row['kid'],
            alg: (string) $row['alg'],
            publicJwk: (string) $row['public_jwk'],
            status: FederationSigningKeyStatus::from((string) $row['status']),
            createdAt: (string) $row['created_at'],
            activatedAt: $activatedAt === null ? null : (string) $activatedAt,
            retiredAt: $retiredAt === null ? null : (string) $retiredAt,
        );
    }
}

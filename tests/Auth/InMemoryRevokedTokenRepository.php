<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Auth;

use NeNeSuite\Auth\RevokedTokenRepositoryInterface;

final class InMemoryRevokedTokenRepository implements RevokedTokenRepositoryInterface
{
    /** @var array<string, int> jti => expires_at epoch */
    private array $revoked = [];

    public function isRevoked(string $jti): bool
    {
        return isset($this->revoked[$jti]);
    }

    public function revoke(string $jti, int $expiresAtEpoch, string $revokedAt, string $reason): void
    {
        $this->revoked[$jti] ??= $expiresAtEpoch;
    }

    public function deleteExpired(int $nowEpoch): void
    {
        foreach ($this->revoked as $jti => $expiresAt) {
            if ($expiresAt < $nowEpoch) {
                unset($this->revoked[$jti]);
            }
        }
    }
}

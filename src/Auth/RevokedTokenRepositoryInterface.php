<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * Denylist of revoked apex session tokens by `jti` (Phase B1.3). Checked on every authenticated
 * request, so `isRevoked` must be cheap (PK lookup). Best-effort, never inside a transaction.
 */
interface RevokedTokenRepositoryInterface
{
    public function isRevoked(string $jti): bool;

    /**
     * Records a revoked token. Idempotent — revoking an already-revoked `jti` is a no-op.
     * `$expiresAtEpoch` is the token's own exp (epoch seconds), after which the row may be pruned.
     */
    public function revoke(string $jti, int $expiresAtEpoch, string $revokedAt, string $reason): void;

    /** Reclaims rows whose token has already expired (expires_at < the epoch threshold). */
    public function deleteExpired(int $nowEpoch): void;
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseConstraintException;
use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\UtcClock;
use Throwable;

final readonly class PdoRevokedTokenRepository implements RevokedTokenRepositoryInterface
{
    public const TOKEN_TYPE_APEX_SESSION = 'apex_session';

    public function __construct(
        private DatabaseQueryExecutorInterface $query,
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function isRevoked(string $jti): bool
    {
        return $this->query->fetchOne('SELECT jti FROM revoked_tokens WHERE jti = ?', [$jti]) !== null;
    }

    public function revoke(string $jti, int $expiresAtEpoch, string $revokedAt, string $reason): void
    {
        try {
            $this->query->execute(
                'INSERT INTO revoked_tokens (jti, token_type, expires_at, revoked_at, reason) VALUES (?, ?, ?, ?, ?)',
                [$jti, self::TOKEN_TYPE_APEX_SESSION, $expiresAtEpoch, $revokedAt, $reason],
            );
        } catch (DatabaseConstraintException) {
            // Idempotent: an already-revoked jti (PK conflict) is a successful no-op. A genuine
            // write failure (connection/other) is NOT swallowed — it must surface so logout fails
            // closed rather than falsely report success while the token stays valid.
            return;
        }

        $this->pruneOccasionally();
    }

    public function deleteExpired(int $nowEpoch): void
    {
        $this->query->execute('DELETE FROM revoked_tokens WHERE expires_at < ?', [$nowEpoch]);
    }

    private function pruneOccasionally(): void
    {
        // ~1% of revocations trigger GC: a row is useless once its token would have expired anyway
        // (expires_at < now), so the verifier already rejects that token on exp.
        if (random_int(1, 100) !== 1) {
            return;
        }

        try {
            $this->deleteExpired($this->clock->now()->getTimestamp());
        } catch (Throwable) {
            // Best-effort cleanup must never fail the revocation it piggybacks on.
        }
    }
}

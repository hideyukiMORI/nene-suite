<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Throwable;

final readonly class PdoLoginAttemptRepository implements LoginAttemptRepositoryInterface
{
    public function __construct(
        private DatabaseQueryExecutorInterface $query,
    ) {
    }

    public function countWithinWindow(string $key, int $windowSeconds, int $now): int
    {
        $row = $this->query->fetchOne('SELECT window_started_at, attempt_count FROM login_attempts WHERE attempt_key = ?', [$key]);

        if ($row === null) {
            return 0;
        }

        $windowStartedAt = (int) ($row['window_started_at'] ?? 0);

        if ($now - $windowStartedAt >= $windowSeconds) {
            return 0;
        }

        return (int) ($row['attempt_count'] ?? 0);
    }

    public function recordFailure(string $key, int $windowSeconds, int $now): int
    {
        $row = $this->query->fetchOne('SELECT window_started_at, attempt_count FROM login_attempts WHERE attempt_key = ?', [$key]);

        if ($row === null) {
            try {
                $this->query->execute(
                    'INSERT INTO login_attempts (attempt_key, window_started_at, attempt_count) VALUES (?, ?, 1)',
                    [$key, $now],
                );
                $this->pruneOccasionally($windowSeconds, $now);

                return 1;
            } catch (Throwable) {
                // Lost a concurrent first-insert race — re-read and fall through to the increment
                // path rather than dropping this failure on the floor.
                $row = $this->query->fetchOne('SELECT window_started_at, attempt_count FROM login_attempts WHERE attempt_key = ?', [$key]);

                if ($row === null) {
                    return 1;
                }
            }
        }

        $windowStartedAt = (int) ($row['window_started_at'] ?? 0);

        if ($now - $windowStartedAt >= $windowSeconds) {
            $this->query->execute(
                'UPDATE login_attempts SET window_started_at = ?, attempt_count = 1 WHERE attempt_key = ?',
                [$now, $key],
            );
            $this->pruneOccasionally($windowSeconds, $now);

            return 1;
        }

        // Atomic increment so concurrent failures from the same key are not lost to a read-modify-write race.
        $this->query->execute(
            'UPDATE login_attempts SET attempt_count = attempt_count + 1 WHERE attempt_key = ?',
            [$key],
        );

        return (int) ($row['attempt_count'] ?? 0) + 1;
    }

    public function clear(string $key): void
    {
        $this->query->execute('DELETE FROM login_attempts WHERE attempt_key = ?', [$key]);
    }

    /**
     * Reclaims rows whose window started before $olderThanEpoch. Keeps the table bounded to roughly
     * the active key set without a scheduler (the entrypoint runs migrate-only; there is no cron).
     */
    public function deleteExpired(int $olderThanEpoch): void
    {
        $this->query->execute('DELETE FROM login_attempts WHERE window_started_at < ?', [$olderThanEpoch]);
    }

    private function pruneOccasionally(int $windowSeconds, int $now): void
    {
        // ~1% of writes trigger GC: amortized cleanup that bounds growth (e.g. an IPv6 prefix or a
        // botnet spraying distinct source IPs) without a dedicated job.
        if (random_int(1, 100) === 1) {
            $this->deleteExpired($now - $windowSeconds);
        }
    }
}

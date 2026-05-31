<?php

declare(strict_types=1);

namespace NeNeSuite\Http;

use Nene2\Config\ConfigLoader;
use Nene2\Config\DatabaseConfig;
use RuntimeException;

/**
 * Resolves the suite control database configuration per ADR 0011.
 *
 * Priority (highest first):
 *  1. `NENE_SUITE_CONTROL_DATABASE_URL` env var — parsed into individual DatabaseConfig fields.
 *  2. NENE2 ConfigLoader — reads DB_* / DATABASE_URL from .env (dev / test fallback).
 *
 * The URL is expected as: `mysql://user:password@host:port/dbname`
 * Parsed individual fields are used by PdoConnectionFactory (not the raw URL string,
 * which is a mysql:// format incompatible with PHP's PDO DSN format).
 *
 * For Phinx migrations, `phinx.php` reads the URL directly when set (Phinx natively
 * supports mysql:// URLs).
 */
final readonly class ControlDatabaseConfigResolver
{
    private const ENV_VAR = 'NENE_SUITE_CONTROL_DATABASE_URL';

    public function resolve(ConfigLoader $fallback): DatabaseConfig
    {
        $url = $_SERVER[self::ENV_VAR] ?? $_ENV[self::ENV_VAR] ?? null;

        if (is_string($url) && $url !== '') {
            return $this->parseUrl($url);
        }

        return $fallback->load()->database;
    }

    /**
     * Returns the raw NENE_SUITE_CONTROL_DATABASE_URL value for tools that
     * accept mysql:// URL format directly (e.g. Phinx migrations).
     */
    public static function rawUrl(): ?string
    {
        $url = $_SERVER[self::ENV_VAR] ?? $_ENV[self::ENV_VAR] ?? null;

        return is_string($url) && $url !== '' ? $url : null;
    }

    private function parseUrl(string $url): DatabaseConfig
    {
        $parsed = parse_url($url);

        if ($parsed === false) {
            throw new RuntimeException("Invalid NENE_SUITE_CONTROL_DATABASE_URL: could not parse '{$url}'.");
        }

        $scheme = $parsed['scheme'] ?? null;
        $host   = $parsed['host'] ?? null;
        $user   = $parsed['user'] ?? null;

        if ($scheme === null || $host === null || $user === null) {
            throw new RuntimeException(
                'NENE_SUITE_CONTROL_DATABASE_URL must be in the form mysql://user:pass@host:port/dbname.',
            );
        }

        $dbName = ltrim($parsed['path'] ?? '', '/');

        if ($dbName === '') {
            throw new RuntimeException('NENE_SUITE_CONTROL_DATABASE_URL must include a database name path segment.');
        }

        return new DatabaseConfig(
            url: null,
            environment: 'production',
            adapter: $scheme,
            host: $host,
            port: isset($parsed['port']) ? (int) $parsed['port'] : 3306,
            name: $dbName,
            user: $user,
            password: $parsed['pass'] ?? '',
            charset: 'utf8mb4',
        );
    }
}

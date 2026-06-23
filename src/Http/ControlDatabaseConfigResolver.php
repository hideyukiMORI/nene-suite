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
 * The URL is expected as: `mysql://user:password@host:port/dbname` or
 * `pgsql://user:password@host:port/dbname` (PostgreSQL — ADR 0016).
 * Parsed individual fields are used by PdoConnectionFactory (not the raw URL string,
 * which is a scheme:// format incompatible with PHP's PDO DSN format).
 *
 * `phinx.php` also consumes the parsed fields (not the raw URL): Phinx does not
 * understand scheme:// URLs and cannot extract the database name from one.
 */
final readonly class ControlDatabaseConfigResolver
{
    private const ENV_VAR = 'NENE_SUITE_CONTROL_DATABASE_URL';

    /** PDO/Phinx adapter default ports. */
    private const DEFAULT_PORTS = ['mysql' => 3306, 'pgsql' => 5432];

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
                'NENE_SUITE_CONTROL_DATABASE_URL must be in the form '
                . 'mysql://user:pass@host:port/dbname or pgsql://user:pass@host:port/dbname.',
            );
        }

        $adapter = self::normalizeAdapter($scheme) ?? throw new RuntimeException(sprintf(
            'NENE_SUITE_CONTROL_DATABASE_URL has unsupported scheme "%s" (use mysql:// or pgsql://).',
            $scheme,
        ));

        $dbName = ltrim($parsed['path'] ?? '', '/');

        if ($dbName === '') {
            throw new RuntimeException('NENE_SUITE_CONTROL_DATABASE_URL must include a database name path segment.');
        }

        return new DatabaseConfig(
            url: null,
            environment: 'production',
            adapter: $adapter,
            host: $host,
            port: isset($parsed['port']) ? (int) $parsed['port'] : self::DEFAULT_PORTS[$adapter],
            name: $dbName,
            user: $user,
            password: $parsed['pass'] ?? '',
            // PostgreSQL ignores charset in the PDO DSN (PdoConnectionFactory), but
            // DatabaseConfig requires a value; 'utf8' is the harmless pgsql placeholder.
            charset: $adapter === 'pgsql' ? 'utf8' : 'utf8mb4',
        );
    }

    /**
     * Adapter (mysql | pgsql) derived from the control URL scheme, or null when the
     * URL is unset or its scheme is unsupported. Used by provisioning to connect with
     * the same engine as the control DB (single source of truth).
     */
    public static function controlAdapter(): ?string
    {
        $url = self::rawUrl();

        if ($url === null) {
            return null;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return is_string($scheme) ? self::normalizeAdapter($scheme) : null;
    }

    /** Normalizes a URL scheme to a PDO/Phinx adapter name, or null when unsupported. */
    private static function normalizeAdapter(string $scheme): ?string
    {
        return match (strtolower($scheme)) {
            'mysql', 'mysqli' => 'mysql',
            'pgsql', 'postgres', 'postgresql' => 'pgsql',
            default => null,
        };
    }
}

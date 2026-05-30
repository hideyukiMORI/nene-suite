<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

/**
 * Redacts secret-bearing values from audit snapshots per
 * docs/explanation/audit-trail.md §5. Replaces matched values with the literal
 * "[REDACTED]" so reviewers know a field existed but was withheld.
 */
final readonly class SuiteAuditSanitizer
{
    public const REDACTED = '[REDACTED]';

    /**
     * Case-insensitive suffixes and exact keys that mark a sensitive value.
     *
     * @var list<string>
     */
    private const SENSITIVE_SUFFIXES = ['_secret', '_password', '_token', '_dsn', '_key'];

    /**
     * @var list<string>
     */
    private const SENSITIVE_EXACT = ['authorization', 'cookie', 'password', 'secret', 'token'];

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public function sanitize(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $result[$key] = self::REDACTED;

                continue;
            }

            $result[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $result;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_SUFFIXES as $suffix) {
            if (str_ends_with($normalized, $suffix)) {
                return true;
            }
        }

        return in_array($normalized, self::SENSITIVE_EXACT, true);
    }
}

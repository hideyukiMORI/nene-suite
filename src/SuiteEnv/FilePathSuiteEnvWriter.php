<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

use RuntimeException;

/**
 * Writes the suite env config to a dotenv-format file at the configured path.
 * Values containing spaces or empty strings are double-quoted.
 * Existing file is overwritten atomically via a temp file + rename.
 */
final readonly class FilePathSuiteEnvWriter implements SuiteEnvWriterInterface
{
    public function __construct(
        private string $filePath,
    ) {
    }

    public function write(SuiteEnvConfig $config): void
    {
        $lines = [];
        foreach ($config->toEnvMap() as $key => $value) {
            $lines[] = $this->formatLine($key, $value);
        }

        $content = implode("\n", $lines) . "\n";
        $tmpPath = $this->filePath . '.tmp';

        if (file_put_contents($tmpPath, $content) === false) {
            throw new RuntimeException("Failed to write env config to {$tmpPath}");
        }

        if (!rename($tmpPath, $this->filePath)) {
            @unlink($tmpPath);
            throw new RuntimeException("Failed to move env config to {$this->filePath}");
        }
    }

    private function formatLine(string $key, string $value): string
    {
        if ($value === '' || str_contains($value, ' ') || str_contains($value, '"')) {
            $escaped = str_replace(['"', '\\'], ['\\"', '\\\\'], $value);

            return "{$key}=\"{$escaped}\"";
        }

        return "{$key}={$value}";
    }
}

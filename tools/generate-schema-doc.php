<?php

declare(strict_types=1);

/**
 * Generates the human-readable control-DB data dictionary (run via `composer schema:docs`).
 *
 * Source of truth: the annotated `database/schema/*.sql` snapshots (per-table DDL with
 * `-- TABLE:` purpose lines and `-- ` column descriptions). Parsing the committed
 * snapshots keeps generation deterministic and DB-free (CI-safe). The real MySQL/PostgreSQL
 * COMMENTs are applied from the same descriptions by the schema-comment migration (ADR 0016).
 *
 * Usage:
 *   php tools/generate-schema-doc.php           # (re)write docs/reference/schema.md
 *   php tools/generate-schema-doc.php --check    # fail (exit 1) if the committed doc is stale
 *
 * Never edit docs/reference/schema.md by hand — regenerate instead.
 */

$root = dirname(__DIR__);
$schemaDir = $root . '/database/schema';
$outputPath = $root . '/docs/reference/schema.md';
$check = in_array('--check', array_slice($argv, 1), true);

$files = glob($schemaDir . '/*.sql');

if ($files === false || $files === []) {
    fwrite(STDERR, "FAIL: no schema snapshots found in {$schemaDir}\n");
    exit(1);
}

sort($files, SORT_STRING);

/** @var list<array{name: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>}> $tables */
$tables = array_map('parse_snapshot', $files);

$markdown = render_markdown($tables);

if ($check) {
    $current = is_file($outputPath) ? (string) file_get_contents($outputPath) : '';

    if ($current === $markdown) {
        echo "OK: docs/reference/schema.md is up to date.\n";
        exit(0);
    }

    fwrite(STDERR, "FAIL: docs/reference/schema.md is stale. Regenerate with `composer schema:docs`.\n");
    exit(1);
}

file_put_contents($outputPath, $markdown);
echo 'Wrote ' . count($tables) . " tables to docs/reference/schema.md\n";
exit(0);

/**
 * @return array{name: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>}
 */
function parse_snapshot(string $path): array
{
    $lines = explode("\n", (string) file_get_contents($path));
    $name = basename($path, '.sql');
    $purpose = '';
    $columns = [];
    $indexes = [];
    $inColumns = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (preg_match('/^--\s*TABLE:\s*(.+)$/', $trimmed, $m) === 1) {
            $purpose = trim($m[1]);
            continue;
        }

        if (preg_match('/^CREATE TABLE\s+(\w+)\s*\($/', $trimmed, $m) === 1) {
            $name = $m[1];
            $inColumns = true;
            continue;
        }

        if ($inColumns && str_starts_with($trimmed, ');')) {
            $inColumns = false;
            continue;
        }

        if ($inColumns) {
            $column = parse_column_line($line);

            if ($column !== null) {
                $columns[] = $column;
            }

            continue;
        }

        if (preg_match('/^CREATE\s+(UNIQUE\s+)?INDEX\s+(\w+)\s+ON\s+\w+\s*\(([^)]+)\)/i', $trimmed, $m) === 1) {
            $indexes[] = [
                'name' => $m[2],
                'unique' => trim($m[1]) !== '',
                'columns' => trim($m[3]),
            ];
        }
    }

    return ['name' => $name, 'purpose' => $purpose, 'columns' => $columns, 'indexes' => $indexes];
}

/**
 * @return array{name: string, type: string, null: string, key: string, description: string}|null
 */
function parse_column_line(string $line): ?array
{
    $description = '';
    $definition = $line;

    if (str_contains($line, '--')) {
        [$definition, $description] = explode('--', $line, 2);
        $description = trim($description);
    }

    $definition = rtrim(trim($definition), ',');

    if ($definition === '') {
        return null; // comment-only line inside the column block
    }

    $parts = preg_split('/\s+/', $definition, 2);

    if ($parts === false || count($parts) < 2) {
        return null;
    }

    [$columnName, $rest] = $parts;

    $null = preg_match('/\bNOT\s+NULL\b/i', $rest) === 1 ? 'NO' : 'YES';
    $key = preg_match('/\bPRIMARY\s+KEY\b/i', $rest) === 1 ? 'PK' : '';

    // Strip nullability/key tokens so the type column shows the data type (+ DEFAULT).
    $type = (string) preg_replace('/\b(PRIMARY\s+KEY|NOT\s+NULL|NULL)\b/i', '', $rest);
    $type = trim((string) preg_replace('/\s+/', ' ', $type));

    return [
        'name' => $columnName,
        'type' => $type,
        'null' => $null,
        'key' => $key,
        'description' => $description,
    ];
}

/**
 * @param list<array{name: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>}> $tables
 */
function render_markdown(array $tables): string
{
    $out = [];
    $out[] = '# Control DB schema reference';
    $out[] = '';
    $out[] = '> **Generated — do not edit by hand.** Run `composer schema:docs` to regenerate.';
    $out[] = '> Source: `database/schema/*.sql` (the same descriptions become MySQL/PostgreSQL';
    $out[] = '> `COMMENT`s via the schema-comment migration — see ADR 0016 / ADR 0014).';
    $out[] = '';
    $out[] = 'Suite control database (`nene_suite`). Sibling application data lives in each';
    $out[] = 'product database, not here.';
    $out[] = '';
    $out[] = '## Tables';
    $out[] = '';

    foreach ($tables as $table) {
        $out[] = '- [`' . $table['name'] . '`](#' . $table['name'] . ') — ' . $table['purpose'];
    }

    $out[] = '';

    foreach ($tables as $table) {
        $out[] = '## `' . $table['name'] . '`';
        $out[] = '';

        if ($table['purpose'] !== '') {
            $out[] = $table['purpose'];
            $out[] = '';
        }

        $out[] = '| Column | Type | Null | Key | Description |';
        $out[] = '| --- | --- | --- | --- | --- |';

        foreach ($table['columns'] as $column) {
            $out[] = sprintf(
                '| `%s` | `%s` | %s | %s | %s |',
                $column['name'],
                $column['type'],
                $column['null'],
                $column['key'],
                md_cell($column['description']),
            );
        }

        $out[] = '';

        if ($table['indexes'] !== []) {
            $out[] = '### Indexes';
            $out[] = '';

            foreach ($table['indexes'] as $index) {
                $kind = $index['unique'] ? 'UNIQUE' : 'index';
                $out[] = sprintf('- `%s` (%s) on `%s`', $index['name'], $kind, $index['columns']);
            }

            $out[] = '';
        }
    }

    return implode("\n", $out) . "\n";
}

/** Escapes a cell value so pipes in descriptions (enum lists) do not break the table. */
function md_cell(string $value): string
{
    return str_replace('|', '\\|', $value);
}

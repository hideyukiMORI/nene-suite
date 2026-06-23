<?php

declare(strict_types=1);

/**
 * Generates the human-readable control-DB data dictionary (run via `composer schema:docs`).
 *
 * Source of truth: the annotated `database/schema/*.sql` snapshots. Per-table headers
 * declare `-- GROUP: <domain>` and `-- TABLE: <purpose>`; each column carries a `-- ` description.
 * Logical references are written inline in a column description as `logical ref <table>.<column>`
 * and rendered as an ER diagram when they point at the target table's primary key (the control
 * DB declares no physical foreign keys — cross-DB FKs are prohibited and intra-DB references are
 * enforced in the use-case layer). Parsing the committed snapshots keeps generation deterministic
 * and DB-free (CI-safe). The same descriptions become MySQL/PostgreSQL COMMENTs via the
 * schema-comment migration (ADR 0016).
 *
 * Usage:
 *   php tools/generate-schema-doc.php           # (re)write docs/reference/schema.md
 *   php tools/generate-schema-doc.php --check    # fail (exit 1) if coverage is incomplete or the doc is stale
 *
 * Never edit docs/reference/schema.md by hand — regenerate instead.
 */

// Overview grouping order; unlisted groups are appended alphabetically.
const GROUP_ORDER = ['Auth', 'Tenancy', 'Install', 'Audit', 'Federation'];

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

$tables = array_map('parse_snapshot', $files);
$relationships = resolve_relationships($tables);
$issues = lint_tables($tables);
$markdown = render_markdown($tables, $relationships);

if ($check) {
    if ($issues !== []) {
        fwrite(STDERR, "FAIL: schema documentation coverage is incomplete:\n");

        foreach ($issues as $issue) {
            fwrite(STDERR, "  - {$issue}\n");
        }

        exit(1);
    }

    $current = is_file($outputPath) ? (string) file_get_contents($outputPath) : '';

    if ($current !== $markdown) {
        fwrite(STDERR, "FAIL: docs/reference/schema.md is stale. Regenerate with `composer schema:docs`.\n");
        exit(1);
    }

    echo "OK: docs/reference/schema.md is complete and up to date.\n";
    exit(0);
}

file_put_contents($outputPath, $markdown);
echo 'Wrote ' . count($tables) . " tables to docs/reference/schema.md\n";

foreach ($issues as $issue) {
    fwrite(STDERR, "warning: {$issue}\n");
}

exit(0);

/**
 * @return array{name: string, group: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>, refs: list<array{from: string, toTable: string, toColumn: string}>}
 */
function parse_snapshot(string $path): array
{
    $lines = explode("\n", (string) file_get_contents($path));
    $name = basename($path, '.sql');
    $group = '';
    $purpose = '';
    $columns = [];
    $indexes = [];
    $refs = [];
    $inColumns = false;

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (preg_match('/^--\s*GROUP:\s*(.+)$/', $trimmed, $m) === 1) {
            $group = trim($m[1]);
            continue;
        }

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

                if (preg_match('/logical ref\s+([a-z0-9_]+)\.([a-z0-9_]+)/i', $column['description'], $r) === 1) {
                    $refs[] = ['from' => $column['name'], 'toTable' => $r[1], 'toColumn' => $r[2]];
                }
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

    return [
        'name' => $name,
        'group' => $group,
        'purpose' => $purpose,
        'columns' => $columns,
        'indexes' => $indexes,
        'refs' => $refs,
    ];
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
 * Resolves declared logical references into ER relationships, keeping only those that point at
 * the target table's primary key (filters out soft groupings such as the shared `suite_id`).
 *
 * @param list<array{name: string, group: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>, refs: list<array{from: string, toTable: string, toColumn: string}>}> $tables
 * @return list<array{from: string, to: string, column: string}>
 */
function resolve_relationships(array $tables): array
{
    $primaryKey = [];

    foreach ($tables as $table) {
        foreach ($table['columns'] as $column) {
            if ($column['key'] === 'PK') {
                $primaryKey[$table['name']] = $column['name'];
                break;
            }
        }
    }

    $relationships = [];

    foreach ($tables as $table) {
        foreach ($table['refs'] as $ref) {
            if (($primaryKey[$ref['toTable']] ?? null) === $ref['toColumn']) {
                $relationships[] = ['from' => $table['name'], 'to' => $ref['toTable'], 'column' => $ref['from']];
            }
        }
    }

    return $relationships;
}

/**
 * Coverage lint: every table needs a group + purpose, and every column a description.
 *
 * @param list<array{name: string, group: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>, refs: list<array{from: string, toTable: string, toColumn: string}>}> $tables
 * @return list<string>
 */
function lint_tables(array $tables): array
{
    $issues = [];

    foreach ($tables as $table) {
        if ($table['group'] === '') {
            $issues[] = "{$table['name']}: missing `-- GROUP:` header";
        }

        if ($table['purpose'] === '') {
            $issues[] = "{$table['name']}: missing `-- TABLE:` header";
        }

        foreach ($table['columns'] as $column) {
            if ($column['description'] === '') {
                $issues[] = "{$table['name']}.{$column['name']}: missing column description";
            }
        }
    }

    return $issues;
}

/**
 * @param list<array{name: string, group: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>, refs: list<array{from: string, toTable: string, toColumn: string}>}> $tables
 * @param list<array{from: string, to: string, column: string}> $relationships
 */
function render_markdown(array $tables, array $relationships): string
{
    $out = [];
    $out[] = '# Control DB schema reference';
    $out[] = '';
    $out[] = '> **Generated — do not edit by hand.** Run `composer schema:docs` to regenerate;';
    $out[] = '> `composer schema:docs:check` enforces coverage + freshness in CI.';
    $out[] = '> Source: `database/schema/*.sql` (the same descriptions become MySQL/PostgreSQL';
    $out[] = '> `COMMENT`s via the schema-comment migration — see ADR 0016 / ADR 0014).';
    $out[] = '';
    $out[] = 'Suite control database (`nene_suite`). Sibling application data lives in each';
    $out[] = 'product database, not here.';
    $out[] = '';

    $out = array_merge($out, render_overview($tables));
    $out = array_merge($out, render_er_diagram($relationships));

    foreach ($tables as $table) {
        $out[] = '## `' . $table['name'] . '`';
        $out[] = '';
        $out[] = '_Group: ' . ($table['group'] !== '' ? $table['group'] : '—') . '_';
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

/**
 * Domain-grouped table index.
 *
 * @param list<array{name: string, group: string, purpose: string, columns: list<array{name: string, type: string, null: string, key: string, description: string}>, indexes: list<array{name: string, unique: bool, columns: string}>, refs: list<array{from: string, toTable: string, toColumn: string}>}> $tables
 * @return list<string>
 */
function render_overview(array $tables): array
{
    $byGroup = [];

    foreach ($tables as $table) {
        $group = $table['group'] !== '' ? $table['group'] : 'Ungrouped';
        $byGroup[$group][] = $table;
    }

    $groups = array_keys($byGroup);
    usort($groups, static function (string $a, string $b): int {
        $ia = array_search($a, GROUP_ORDER, true);
        $ib = array_search($b, GROUP_ORDER, true);
        $ra = $ia === false ? PHP_INT_MAX : $ia;
        $rb = $ib === false ? PHP_INT_MAX : $ib;

        return $ra === $rb ? strcmp($a, $b) : $ra <=> $rb;
    });

    $out = ['## Tables', ''];

    foreach ($groups as $group) {
        $out[] = '### ' . $group;
        $out[] = '';

        foreach ($byGroup[$group] as $table) {
            $out[] = '- [`' . $table['name'] . '`](#' . $table['name'] . ') — ' . $table['purpose'];
        }

        $out[] = '';
    }

    return $out;
}

/**
 * Mermaid ER diagram of the logical references (no physical FKs in the control DB).
 *
 * @param list<array{from: string, to: string, column: string}> $relationships
 * @return list<string>
 */
function render_er_diagram(array $relationships): array
{
    if ($relationships === []) {
        return [];
    }

    $out = [];
    $out[] = '## Relationships';
    $out[] = '';
    $out[] = 'Logical references only — the control DB declares no physical foreign keys';
    $out[] = '(cross-DB FKs are prohibited; intra-DB references are enforced in the use-case layer).';
    $out[] = '';
    $out[] = '```mermaid';
    $out[] = 'erDiagram';

    foreach ($relationships as $relationship) {
        // target (one) to source (many), labelled with the referencing column.
        $out[] = sprintf('  %s ||--o{ %s : "%s"', $relationship['to'], $relationship['from'], $relationship['column']);
    }

    $out[] = '```';
    $out[] = '';

    return $out;
}

/** Escapes a cell value so pipes in descriptions (enum lists) do not break the table. */
function md_cell(string $value): string
{
    return str_replace('|', '\\|', $value);
}

#!/usr/bin/env bash
# Validate catalog/apps.json against catalog/apps.schema.json plus semantic rules
# that JSON Schema cannot express: dependency graph must be a DAG, and ids /
# paths / env prefixes / provides tokens must be unique.
#
# Self-contained: python3 standard library only, no network, no extra deps —
# mirrors tools/check-terminology.sh. Exit non-zero on any error.
#
# Usage: tools/validate-catalog.sh [path/to/apps.json]
# Rules: docs/development/schema-conventions.md, docs/explanation/terminology.md
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
CATALOG="${1:-$ROOT/catalog/apps.json}"
SCHEMA="$ROOT/catalog/apps.schema.json"

python3 - "$CATALOG" "$SCHEMA" <<'PY'
import json
import re
import sys

catalog_path, schema_path = sys.argv[1], sys.argv[2]

errors: list[str] = []
warnings: list[str] = []


def err(msg: str) -> None:
    errors.append(msg)


def warn(msg: str) -> None:
    warnings.append(msg)


def load(path: str, label: str):
    try:
        with open(path, encoding="utf-8") as fh:
            return json.load(fh)
    except FileNotFoundError:
        print(f"FAIL: {label} not found: {path}", file=sys.stderr)
        sys.exit(1)
    except json.JSONDecodeError as exc:
        print(f"FAIL: {label} is not valid JSON: {exc}", file=sys.stderr)
        sys.exit(1)


schema = load(schema_path, "schema")
data = load(catalog_path, "catalog")


def type_ok(val, expected) -> bool:
    if isinstance(expected, list):
        return any(type_ok(val, e) for e in expected)
    checks = {
        "string": lambda v: isinstance(v, str),
        "integer": lambda v: isinstance(v, int) and not isinstance(v, bool),
        "number": lambda v: isinstance(v, (int, float)) and not isinstance(v, bool),
        "boolean": lambda v: isinstance(v, bool),
        "array": lambda v: isinstance(v, list),
        "object": lambda v: isinstance(v, dict),
        "null": lambda v: v is None,
    }
    return checks.get(expected, lambda v: True)(val)


# ── Structural validation, driven by apps.schema.json (stays in sync) ─────────
top_props = schema.get("properties", {})
for req in schema.get("required", []):
    if req not in data:
        err(f"top-level: missing required key '{req}'")

version = data.get("version")
if version is not None and not type_ok(version, top_props.get("version", {}).get("type", "integer")):
    err(f"top-level: 'version' must be an integer (got {version!r})")
min_version = top_props.get("version", {}).get("minimum")
if isinstance(version, int) and min_version is not None and version < min_version:
    err(f"top-level: 'version' must be >= {min_version} (got {version})")

apps = data.get("apps")
if not isinstance(apps, list):
    print("FAIL: 'apps' must be an array.", file=sys.stderr)
    sys.exit(1)

item_schema = top_props.get("apps", {}).get("items", {})
item_required = item_schema.get("required", [])
item_props = item_schema.get("properties", {})
id_pattern = item_props.get("id", {}).get("pattern", "^nene-[a-z0-9-]+$")
status_enum = item_props.get("status", {}).get("enum", ["planned", "installable", "deprecated"])
env_prefix_re = re.compile(r"^NENE_[A-Z0-9_]+_$")


def field_type(name: str):
    return item_props.get(name, {}).get("type")


for i, app in enumerate(apps):
    where = f"apps[{i}]"
    if not isinstance(app, dict):
        err(f"{where}: must be an object")
        continue
    label = app.get("id", where)
    for req in item_required:
        if req not in app:
            err(f"{label}: missing required field '{req}'")

    app_id = app.get("id")
    if app_id is not None:
        if not isinstance(app_id, str) or not re.match(id_pattern, app_id):
            err(f"{label}: 'id' must match {id_pattern} (got {app_id!r})")

    for fname in ("name", "path"):
        if fname in app and not type_ok(app[fname], field_type(fname) or "string"):
            err(f"{label}: '{fname}' has wrong type")

    if "repository" in app and not type_ok(app["repository"], field_type("repository") or ["string", "null"]):
        err(f"{label}: 'repository' must be string or null")

    status = app.get("status")
    if status is not None and status not in status_enum:
        err(f"{label}: 'status' must be one of {status_enum} (got {status!r})")

    for fname in ("requires", "provides"):
        val = app.get(fname)
        if val is not None:
            if not isinstance(val, list):
                err(f"{label}: '{fname}' must be an array")
            elif not all(isinstance(x, str) for x in val):
                err(f"{label}: '{fname}' must contain only strings")

    if "install_entry" in app:
        entry = app["install_entry"]
        if not isinstance(entry, str) or not entry.startswith("/"):
            err(f"{label}: 'install_entry' must be a path starting with '/' (got {entry!r})")

    db = app.get("database")
    if db is not None:
        if not isinstance(db, dict):
            err(f"{label}: 'database' must be an object")
        else:
            prefix = db.get("env_prefix")
            if prefix is None:
                err(f"{label}: 'database.env_prefix' is required when 'database' is present")
            elif not isinstance(prefix, str) or not env_prefix_re.match(prefix):
                err(f"{label}: 'database.env_prefix' must match {env_prefix_re.pattern} (got {prefix!r})")
            elif prefix.startswith("NENE_SUITE_"):
                err(f"{label}: 'database.env_prefix' must not use the suite NENE_SUITE_ prefix (got {prefix!r})")

# ── Semantic rules (beyond JSON Schema) ──────────────────────────────────────
ids: list[str] = [a["id"] for a in apps if isinstance(a, dict) and isinstance(a.get("id"), str)]
id_set = set(ids)

for dup in sorted({x for x in ids if ids.count(x) > 1}):
    err(f"duplicate catalog id: {dup}")

paths: dict[str, str] = {}
prefixes: dict[str, str] = {}
provides_owner: dict[str, str] = {}
for app in apps:
    if not isinstance(app, dict):
        continue
    label = app.get("id", "<no-id>")
    path = app.get("path")
    if isinstance(path, str):
        if path in paths:
            err(f"duplicate path '{path}' shared by {paths[path]} and {label}")
        else:
            paths[path] = label
    prefix = (app.get("database") or {}).get("env_prefix") if isinstance(app.get("database"), dict) else None
    if isinstance(prefix, str):
        if prefix in prefixes:
            err(f"duplicate database.env_prefix '{prefix}' shared by {prefixes[prefix]} and {label}")
        else:
            prefixes[prefix] = label
    for token in app.get("provides", []) if isinstance(app.get("provides"), list) else []:
        if token in provides_owner and provides_owner[token] != label:
            err(f"'provides' token '{token}' owned by multiple apps: {provides_owner[token]} and {label}")
        else:
            provides_owner[token] = label

# Dependency edges: validate references, self-loops, and status sanity.
graph: dict[str, list[str]] = {}
for app in apps:
    if not isinstance(app, dict) or not isinstance(app.get("id"), str):
        continue
    app_id = app["id"]
    deps = [d for d in app.get("requires", []) if isinstance(d, str)]
    graph[app_id] = []
    for dep in deps:
        if dep == app_id:
            err(f"{app_id}: must not require itself")
            continue
        if dep not in id_set:
            err(f"{app_id}: requires undefined catalog id '{dep}'")
            continue
        graph[app_id].append(dep)
        dep_status = next((a.get("status") for a in apps if isinstance(a, dict) and a.get("id") == dep), None)
        if app.get("status") == "installable" and dep_status != "installable":
            warn(f"{app_id} (installable) requires '{dep}' which is '{dep_status}' — install would be blocked")

# Cycle detection (the requires graph must be a DAG — schema-conventions.md).
WHITE, GREY, BLACK = 0, 1, 2
color = {node: WHITE for node in graph}
seen_cycles: set[frozenset] = set()


def visit(node: str, stack: list[str]) -> None:
    color[node] = GREY
    stack.append(node)
    for nxt in graph.get(node, []):
        if color.get(nxt) == GREY:
            cycle = stack[stack.index(nxt):]
            key = frozenset(cycle)
            if key not in seen_cycles:
                seen_cycles.add(key)
                err("dependency cycle (requires must be a DAG): " + " -> ".join(cycle + [nxt]))
        elif color.get(nxt) == WHITE:
            visit(nxt, stack)
    color[node] = BLACK
    stack.pop()


for node in graph:
    if color[node] == WHITE:
        visit(node, [])

# ── Report ───────────────────────────────────────────────────────────────────
for w in warnings:
    print(f"WARN: {w}", file=sys.stderr)

if errors:
    for e in errors:
        print(f"FAIL: {e}", file=sys.stderr)
    print(f"\nCatalog validation failed with {len(errors)} error(s): {catalog_path}", file=sys.stderr)
    print("See docs/development/schema-conventions.md", file=sys.stderr)
    sys.exit(1)

print(f"Catalog validation passed: {len(apps)} app(s) in {catalog_path}")
if warnings:
    print(f"({len(warnings)} warning(s) — see above)")
PY

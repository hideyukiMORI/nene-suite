# Schema Conventions

JSON Schema, catalog, and database schema rules for NeNe Suite. All structured
data outside PHP DTOs **MUST** follow this document and
[`terminology.md`](../explanation/terminology.md).

**Enforcement:** CI validates catalog + orchestration schemas (Issue #9+).
Schema drift **blocks merge**.

---

## Principles

| Principle | Meaning |
| --- | --- |
| **Registered fields only** | New JSON keys require `terminology.md` update in same PR |
| **snake_case properties** | JSON columns, manifest fields, audit events — not camelCase |
| **Stable `$id`** | Each schema file has permanent HTTPS `$id` |
| **No secrets in schemas** | Examples use placeholders; never real tokens |
| **Schema + doc pair** | Binding behavior in markdown; structure in JSON Schema |

---

## File locations

| Artifact | Path | Validated by |
| --- | --- | --- |
| App catalog data | `catalog/apps.json` | `tools/validate-catalog.sh` (schema + DAG) |
| App catalog schema | `catalog/apps.schema.json` | `tools/validate-catalog.sh` |
| Audit event shape | `schema/suite-audit-event.schema.json` | CI + manual review |
| Install manifest shape | `schema/install-manifest.schema.json` | Phase 1+ CI |
| SQL snapshots | `database/schema/{table}.sql` | Review vs Phinx migrations |
| OpenAPI | `docs/openapi/openapi.yaml` | `composer openapi` |

Do **not** add ad-hoc JSON config files under `config/` without schema + Issue.

---

## JSON Schema authoring

### Header (required)

```json
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "$id": "https://nene-suite.dev/schemas/{name}.json",
  "title": "PascalCaseTitle",
  "description": "English one-line purpose",
  "type": "object",
  "additionalProperties": false
}
```

- Draft **2020-12** unless ADR changes
- Top-level objects: `"additionalProperties": false` unless extensibility is documented
- Use `"enum"` for closed vocabularies (`source`, `entity_type`, catalog `status`)
- ULID fields: pattern `^[0-9A-HJKMNP-TV-Z]{26}$` (same as audit schema)

### `$id` registry

| Schema | `$id` |
| --- | --- |
| App catalog | `https://nene-suite.dev/catalog/apps.schema.json` |
| Suite audit event | `https://nene-suite.dev/schemas/suite-audit-event.json` |
| Install manifest | `https://nene-suite.dev/schemas/install-manifest.json` |

Changing `$id` or breaking required fields requires ADR + sibling coordination if consumed externally.

---

## Catalog schema rules

Authoritative: [`catalog/apps.schema.json`](../../catalog/apps.schema.json).

| Field | Rules |
| --- | --- |
| `apps[].id` | Pattern `^nene-[a-z0-9-]+$`; registered in terminology |
| `apps[].status` | `planned` \| `installable` \| `deprecated` |
| `apps[].requires` | Array of catalog ids; must form DAG (validated by `tools/validate-catalog.sh` and, later, the install use case) |
| `apps[].database.env_prefix` | Documents sibling env prefix — not suite `NENE_SUITE_*` |

When adding catalog fields:

1. Update `apps.schema.json`
2. Update `terminology.md`
3. Update `docs/integrations/sibling-products.md` if operator-facing

---

## Audit event schema

Authoritative: [`schema/suite-audit-event.schema.json`](../../schema/suite-audit-event.schema.json).

- Must stay aligned with [`audit-trail.md`](../explanation/audit-trail.md) §3–§4
- New `entity_type` or `action` patterns: update markdown §4 **and** schema `enum` in same PR
- `before_json` / `after_json`: type `object` or `null` — inner shape documented per action in markdown, not duplicated in schema (avoid double maintenance) unless ADR requires strict sub-schema

---

## Install manifest schema (Phase 1+)

File: `schema/install-manifest.schema.json` (create when installer ADR lands).

Minimum required properties (register exact names in terminology §10):

- `suite_id`, `installed_at`, `app_versions`, `org_external_id`, `enabled_integrations`
- Per-app: public URL, database **name** — not passwords
- Optional: manifest content hash for audit alignment

---

## Database schema (SQL)

A generated, human-readable data dictionary for every control-DB table lives at
[`../reference/schema.md`](../reference/schema.md). It is produced from the
`database/schema/*.sql` snapshots by `composer schema:docs` (verified fresh in CI via
`composer schema:docs:check`) — never edit it by hand.

### Control DB tables

Naming: **snake_case**, plural table names.

Audit table columns **MUST** match audit-trail.md:

```sql
-- Illustrative — exact DDL in Phinx migration
CREATE TABLE suite_audit_events (
  id CHAR(26) NOT NULL PRIMARY KEY,
  suite_id CHAR(26) NOT NULL,
  org_external_id CHAR(26) NULL,
  actor_user_id CHAR(26) NULL,
  actor_label VARCHAR(320) NULL,
  action VARCHAR(128) NOT NULL,
  entity_type VARCHAR(64) NOT NULL,
  entity_id VARCHAR(64) NOT NULL,
  before_json JSON NULL,
  after_json JSON NULL,
  created_at TIMESTAMP NOT NULL,
  source VARCHAR(32) NOT NULL,
  request_id VARCHAR(128) NULL,
  install_session_id CHAR(26) NULL,
  metadata_json JSON NULL
);
```

Rules:

- Every new table: Phinx migration + `database/schema/{table}.sql` snapshot
- JSON columns for snapshots — not separate before/after tables
- **No FK** from suite control DB into sibling app databases

---

## OpenAPI schema naming

Follow NENE2 OpenAPI conventions:

- Component schemas: PascalCase (`InstallSessionResponse`, `AppCatalogEntry`)
- Property names in JSON payloads: **camelCase** in OpenAPI (HTTP JSON convention) — map to snake_case DB in mappers
- Document mapping in handler/repository — never expose raw DB row shapes

Problem Details components reuse NENE2 patterns; suite-specific problems under `https://nene-suite.dev/problems/`.

---

## Validation workflow

```bash
# Phase 1+ (composer script)
composer catalog:validate

# Manual until CI lands
npx --yes ajv-cli validate -s catalog/apps.schema.json -d catalog/apps.json
npx --yes ajv-cli validate -s schema/suite-audit-event.schema.json -d path/to/fixture.json
```

Every schema change PR includes:

1. Updated schema file
2. Updated fixture or catalog instance proving validity
3. Terminology / audit-trail updates if fields or enums changed

---

## Related documents

- [`audit-trail.md`](../explanation/audit-trail.md)
- [`naming-conventions.md`](./naming-conventions.md)
- [`backend-standards.md`](./backend-standards.md)
- NENE2: `docs/integrations/openapi.md`

Last updated: 2026-05-29

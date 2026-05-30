# Schema Self-Review

Use for changes to `catalog/*.json`, `schema/*.schema.json`, and SQL snapshots.

Sources: [`../development/schema-conventions.md`](../development/schema-conventions.md),
[`../explanation/terminology.md`](../explanation/terminology.md).

## Checklist

- [ ] JSON Schema uses draft 2020-12 and stable `$id`.
- [ ] Top-level `additionalProperties: false` unless extensibility documented.
- [ ] New property names registered in `terminology.md` (same PR).
- [ ] Catalog `id` values match `^nene-[a-z0-9-]+$`.
- [ ] Audit schema enums aligned with `audit-trail.md` §4 when changed.
- [ ] No secrets in examples or default fixtures.
- [ ] Validation command run or documented in PR (ajv / future `composer catalog:validate`).
- [ ] Install manifest fields match orchestration-compliance §6.1.

Mark `N/A` when no schema files touched.

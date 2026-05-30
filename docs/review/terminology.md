# Terminology Self-Review

**Binding.** Use for **any** change to docs, `catalog/apps.json`, `.env.suite.example`,
installer UI copy (English repo text), or scripts that introduce identifiers.

Source of truth: [`../explanation/terminology.md`](../explanation/terminology.md).

## Checklist

- [ ] Every new env var, JSON field, JWT claim, or catalog token appears in `terminology.md` (same PR).
- [ ] Spelling matches the registry exactly — case, hyphens, underscores, prefixes.
- [ ] No forbidden variants from terminology §4–§7, §12 (grep if unsure).
- [ ] Product names use **NeNe Suite**, **NeNe Invoice**, etc. — not lowercase slug as display name.
- [ ] `NENE_SUITE_*` prefix used for all suite orchestrator env vars — not `SUITE_*` or `NENE2_SUITE_*`.
- [ ] Catalog `id` values use `nene-` hyphen form — not underscores.
- [ ] SSOT / system of record used per terminology §7 — Suite is never labeled billing SSOT.
- [ ] 士業 terms (税理士, 公認会計士, 弁護士) spelled exactly when used.
- [ ] Sibling identifiers (e.g. `NENE2_LOCAL_JWT_SECRET`, `external_id`) match sibling conventions — not renamed.
- [ ] Internal markdown links resolve (no `../nene-records/` repo-relative paths — use GitHub URLs for siblings).
- [ ] Audit field names (`before_json`, `after_json`, `suite_audit_events`, `SuiteAuditRecorder`) match terminology §9.
- [ ] New audit `action` values registered in audit-trail.md §4 before use in code or schema.

# ADR 0007: Suite Audit Trail — Before/After for All Operator Mutations

## Status

accepted

## Context

The maintainer requires NeNe Suite to support **professional review and operator
accountability**: for every orchestration mutation, record **who** changed **what**,
with **before and after** sanitized state — not only a final install manifest.

Sibling products already commit to this pattern:

- nene-invoice [ADR 0008](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/adr/0008-audit-logging.md) — `audit_logs` with `before_json` / `after_json`
- nene-vault [ADR 0014](https://github.com/hideyukiMORI/nene-vault/blob/main/docs/adr/0014-audit-event-schema.md) — UseCase-layer `AuditRecorder`, same-transaction write

NeNe Suite is an **orchestrator** ([ADR 0002](./0002-orchestrator-not-application-monolith.md)).
Its audit scope is **installer and apex configuration**, not domain records in
Invoice / Clear / Vault / Records databases.

[`orchestration-compliance.md`](../explanation/orchestration-compliance.md) §6
already requires an install manifest. That snapshot alone does not capture wizard
steps, failures, incremental changes, or integration toggle history.

## Decision

1. **Binding spec:** [`docs/explanation/audit-trail.md`](../explanation/audit-trail.md)
   governs all suite audit behavior from Phase 1 implementation onward.

2. **Dedicated control store:** Append-only `suite_audit_events` table in a
   **`nene_suite` control database**, separate from per-app databases. No
   cross-database writes into sibling domain tables.

3. **Event shape:** ULID `id`, `suite_id`, optional `org_external_id`, actor
   fields, `{entity}.{verb}` `action`, `entity_type` / `entity_id`,
   `before_json` / `after_json`, `created_at`, `source`, optional
   `request_id` / `install_session_id` / `metadata_json`. Canonical JSON Schema:
   [`schema/suite-audit-event.schema.json`](../../schema/suite-audit-event.schema.json).

4. **Recording layer:** Orchestration UseCase / command handlers invoke
   `SuiteAuditRecorder` — **not** middleware-only logging. Mutation + audit row
   **MUST** share one DB transaction when the mutation is transactional.

5. **Sanitization:** Same rule as Invoice ADR 0008 — reuse sanitized config
   presenters; never persist secrets; redact with `"[REDACTED]"`.

6. **Immutability:** No UPDATE/DELETE on audit rows. Corrections are new events.

7. **Manifest relationship:** Install manifest remains required (compliance §6);
   audit trail is the chronological superset. `install_session.completed` SHOULD
   align manifest content with `after_json`.

8. **Read surface (follow-up):** Phase 2+ MAY add paginated apex admin HTTP
   export. Phase 1 MAY document CLI/SQL export only.

## Consequences

**Benefits**

- Operators and 士業 can reconstruct suite configuration history without
  inferring from `.env` mtime or git.
- Aligns portfolio audit vocabulary (`before_json`, `after_json`, UseCase recording)
  across Invoice, Vault, and Suite.
- Clear boundary: suite audit ≠ sibling domain audit.

**Costs**

- Suite implementation needs a small control DB and recorder abstraction.
- Every new mutating installer step must register an `action` in audit-trail.md §4.
- Slightly larger storage for long-running installs and add-app operations.

**Non-goals**

- Does not certify statutory compliance or replace sibling product audit logs.
- Does not audit read-only browsing or sibling app API calls made **after** install
  unless re-wired through suite apex admin.

## Related

- Issue: #12
- [`docs/explanation/audit-trail.md`](../explanation/audit-trail.md)
- [`docs/explanation/orchestration-compliance.md`](../explanation/orchestration-compliance.md) §6
- [ADR 0005](./0005-orchestration-compliance-binding.md)
- nene-invoice ADR 0008, nene-vault ADR 0014

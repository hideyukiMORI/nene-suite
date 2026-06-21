# Suite Audit Trail — Binding Rules

**Status: binding (Phase 1+).** Every **mutating** operator action performed by
the NeNe Suite orchestrator **MUST** record who changed what, including
**before and after** sanitized state, so a reviewer (税理士 / 公認会計士 / 弁護士 /
operator) can reconstruct the full history of suite configuration without reading
scattered `.env` files or inferring intent from filesystem timestamps alone.

NeNe Suite audit covers **orchestration only** — install sessions, app selection,
env wiring (non-secret fields), integration toggles, manifest updates, and apex
operator settings. **Domain mutations** (invoices, reconciliation rows, vault
documents, CMS records) remain each sibling product's own audit trail per that
product's compliance docs.

This is **not legal advice**. It is engineering's binding requirement for
traceability at the installer boundary. See also:
[`orchestration-compliance.md`](./orchestration-compliance.md) §6,
[ADR 0007](../adr/0007-suite-audit-trail-before-after.md),
[nene-invoice ADR 0008](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/adr/0008-audit-logging.md),
[nene-vault ADR 0014](https://github.com/hideyukiMORI/nene-vault/blob/main/docs/adr/0014-audit-event-schema.md).

---

## 1. Scope — what MUST be audited

| Category | Examples | Audited? |
| --- | --- | --- |
| Install wizard steps | app selected/deselected, disclaimer accepted, install started/completed/failed | **YES** |
| Env / wiring changes | `NENE_SUITE_*` non-secret values, per-app public URLs, DB **names** (not passwords) | **YES** |
| Integration toggles | Clear → Invoice HTTP wiring enabled/disabled | **YES** |
| Incremental add-app | new app added to existing suite | **YES** |
| Install manifest | create / update snapshot | **YES** (as entity) |
| Apex operator settings | org display name change, installed-apps list change | **YES** |
| Catalog pin change | suite pins sibling version in manifest | **YES** |
| Read-only navigation | viewing disclaimer, catalog list, health checks | **NO** |
| Sibling app domain CRUD | quote issued, payment recorded, document uploaded | **NO** — sibling SSOT |

**Rule:** If the operation **persists** suite orchestration state or **mutates**
filesystem/env/config the suite owns, it **MUST** emit one audit event unless
explicitly listed as read-only above.

---

## 2. Storage — suite control database

Per [ADR 0002](../adr/0002-orchestrator-not-application-monolith.md), suite
**MUST NOT** store orchestration audit rows in sibling app databases.

| Store | Purpose |
| --- | --- |
| **`nene_suite` control database** | Append-only `suite_audit_events` table + install manifest row(s) |
| **Install manifest file** | Human-exportable snapshot at install completion (§6 of orchestration-compliance) |

The control DB is **orchestration metadata only** — no Invoice lines, Clear
evidence blobs, or Vault file bytes.

Environment variable (Phase 1+): `NENE_SUITE_CONTROL_DATABASE_URL` — resolution
strategy documented in [ADR 0011](../adr/0011-control-database-url-resolution.md).
Canonical name registered in [`terminology.md`](./terminology.md).

---

## 3. Event schema (canonical)

One row per mutating operation. Field names are binding; see
[`schema/suite-audit-event.schema.json`](../../schema/suite-audit-event.schema.json).

| Column | Type | Meaning |
| --- | --- | --- |
| `id` | ULID | Primary key |
| `suite_id` | ULID | Matches `NENE_SUITE_ID` / manifest `suite_id` |
| `org_external_id` | ULID nullable | Federation key when known |
| `actor_user_id` | ULID nullable | Authenticated apex user; NULL for pre-auth wizard / system |
| `actor_label` | VARCHAR nullable | Display fallback (e.g. operator email at disclaimer step) — no secrets |
| `action` | VARCHAR | `{entity}.{verb}` — see §4 |
| `entity_type` | VARCHAR | See §4 |
| `entity_id` | VARCHAR | Stable id of affected orchestration entity (ULID or `suite_id`) |
| `before_json` | JSON nullable | Sanitized snapshot **before** mutation; NULL on create |
| `after_json` | JSON nullable | Sanitized snapshot **after** mutation; NULL on hard delete (forbidden — see §7) |
| `created_at` | TIMESTAMP | UTC, server-generated |
| `source` | VARCHAR | `installer_ui` \| `installer_cli` \| `apex_admin` \| `system` \| `api` |
| `request_id` | VARCHAR nullable | Correlates with HTTP `X-Request-Id` when present |
| `install_session_id` | ULID nullable | Groups wizard steps for one install run |
| `metadata_json` | JSON nullable | Event-specific extras (failure reason, dependency list, IP hash) |

**Diff rule:** Reviewers derive field-level changes from `before_json` and
`after_json`. Implementations **MUST NOT** rely on a separate diff column unless
an ADR adds one.

---

## 4. Event types (Phase 1 minimum)

Action pattern: `{entity}.{verb}`. Register new actions in this section before
code merge.

| Action | entity_type | before_json | after_json | Notes |
| --- | --- | --- | --- | --- |
| `install_session.started` | `install_session` | NULL | session snapshot | Includes Tier, catalog revision |
| `install_session.completed` | `install_session` | in-progress | completed snapshot | Links to manifest entity_id |
| `install_session.failed` | `install_session` | in-progress | failed snapshot | `metadata_json.failure_code` required |
| `app_selection.changed` | `app_selection` | prior selection | new selection | Full list of catalog ids + dependency resolution |
| `disclaimer.accepted` | `disclaimer_acknowledgment` | NULL | ack snapshot | Version string + timestamp; operator label |
| `env_config.written` | `suite_env_config` | prior sanitized env map | new sanitized env map | Secrets redacted per §5 |
| `database.provisioned` | `app_database` | NULL | `{app_id, database_name}` | **No** connection passwords |
| `integration.enabled` | `integration_wiring` | disabled snapshot | enabled snapshot | e.g. Clear → Invoice; scopes list |
| `integration.disabled` | `integration_wiring` | enabled snapshot | disabled snapshot | |
| `manifest.created` | `install_manifest` | NULL | manifest body | No secrets |
| `manifest.updated` | `install_manifest` | prior manifest | updated manifest | Incremental add-app |
| `org_display_name.changed` | `suite_org_profile` | `{org_name: …}` | `{org_name: …}` | Does not change tax registration |
| `catalog_pin.changed` | `catalog_pin` | prior pins | new pins | Sibling version pins only |
| `apex_operator.created` | `apex_operator` | NULL | `{id, email, displayName}` | Installer provisions first operator; password hash omitted |
| `organization.created` | `organization` | NULL | `{id, externalId, name, slug, status}` | Suite registry row; identity / roster only — never sibling domain data (ADR 0012 §11) |
| `organization.renamed` | `organization` | `{name: …}` | `{name: …}` | Identity only; supersedes the legacy `org_display_name.changed` / `suite_org_profile` path |
| `organization.disabled` | `organization` | active snapshot | disabled snapshot | Soft-disable only (`OrganizationStatus`); hard delete forbidden (§7) |
| `membership.granted` | `membership` | NULL | `{operator_id, organization_id, role}` | `organization_id` is null for a platform `superadmin` membership |
| `membership.role_changed` | `membership` | `{role: …}` | `{role: …}` | Supersedes the pre-registered `apex_operator.role_changed` (never implemented) |
| `membership.revoked` | `membership` | membership snapshot | NULL | Records link removal; the audit row itself stays append-only (§7) |

The multi-tenant entity types `organization` / `membership` were registered
2026-06-21 (milestone A0, decision §1); emitters land in milestone A2/A3.
`suite_org_profile` is retained as legacy (declared-but-unused) and is **not**
reused for these — see
[`../milestones/2026-06-multi-tenant-suite.md`](../milestones/2026-06-multi-tenant-suite.md) §1.

Phase 2+ actions (register before implementation): `app.added`, `app.removed`,
`apex_operator.invited`. (`apex_operator.role_changed` is **superseded** by
`membership.role_changed` above and will not be implemented.)

---

## 5. Sanitization — MUST / MUST NOT

### MUST

- Produce `before_json` / `after_json` from the **same presenters** used for
  operator-facing config export (sanitized view).
- Redact or omit values for keys matching (case-insensitive suffix): `_SECRET`,
  `_PASSWORD`, `_TOKEN`, `_DSN` when it embeds credentials, `Authorization`,
  `Cookie`, raw JWT strings.
- Replace redacted values with the literal string `"[REDACTED]"` so reviewers
  know a field existed but was withheld.
- Record **database names** and **public URLs** — operators need them for review.
- Write audit rows in the **same transaction** as the orchestration mutation when
  a DB transaction exists (mirrors nene-vault ADR 0014).

### MUST NOT

- Write passwords, JWT secrets, service tokens, or full `.env` dumps to
  `before_json`, `after_json`, `metadata_json`, or install manifest.
- Write sibling **domain** payloads (invoice totals, reconciliation rows).
- **Update or delete** existing audit rows (append-only store).
- Present the audit log as proof of **statutory** compliance — it proves
  **configuration history** only.

---

## 6. Recording location

Rejected layers (same rationale as nene-invoice ADR 0008):

1. **HTTP middleware only** — no domain before/after, wrong action names.
2. **Shell script echo** — no actor, not transactional, not queryable.

**Chosen:** orchestration **UseCase / command handler** layer (installer wizard
step handlers, apex admin commands) calls `SuiteAuditRecorder.record(...)` after
fetching "before" state and committing mutation.

Each handler **MUST**:

1. Resolve `actor_user_id` / `actor_label` from apex auth or wizard session.
2. Load sanitized `before_json` when the entity pre-exists.
3. Execute mutation (env write, DB create, manifest upsert).
4. Record `after_json` in the same transaction when applicable.

System-generated events (`source: system`) use NULL `actor_user_id` and MUST
document trigger in `metadata_json` (e.g. `{"trigger": "retry_job"}`).

---

## 7. Retention and immutability

- Audit events are **append-only**. Corrections are new events (e.g.
  `env_config.written` with corrected after state), never row overwrites.
- **Hard delete of audit rows is forbidden.** If law or policy ever requires
  purging, that requires a new ADR + professional sign-off — not implemented in
  Phase 1.
- Export: Phase 2+ **MAY** add `GET /admin/suite-audit-events` (paginated,
  operator-auth) for apex admins. Phase 1 **MAY** ship SQL/CLI export documented
  in installer ADR.

---

## 8. Relationship to install manifest

The **install manifest** is a **point-in-time snapshot** at install completion
(orchestration-compliance §6). The **audit trail** is the **full chronological
history** including partial wizard steps, failed attempts, and later changes.

When `install_session.completed` fires, `after_json` **SHOULD** reference the
same content as the manifest (by hash or embedded copy) so reviewers can align
snapshot vs timeline.

---

## 9. Professional review

士業 reviewing the suite boundary **SHOULD** verify:

- SSOT and DB separation unchanged (orchestration-compliance §2–§3).
- Audit covers all installer mutations listed in §1.
- No secrets appear in sample exports.
- Before/after sufficient to explain Clear → Invoice wiring and app selection.

Template: [`professional-sign-off-record.md`](./professional-sign-off-record.md).

---

## 10. Change control

New `action` values, columns, or redaction rules require:

1. GitHub Issue.
2. Update this file + [`terminology.md`](./terminology.md) + JSON schema.
3. ADR amendment if retention or storage layer changes.
4. Self-review: [`../review/compliance.md`](../review/compliance.md).

---

Last updated: 2026-05-29

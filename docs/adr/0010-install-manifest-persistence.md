# ADR 0010: Install Manifest Persistence and Phase 1 Minimal Content

## Status

accepted

## Context

When an operator completes an install session, NeNe Suite must write a
**point-in-time install manifest** — the snapshot a reviewer (operator / 士業)
reads to see *what was installed, for which organization, and when*
(orchestration-compliance §6, requirements R-05). The manifest field names are
registered in [`terminology.md`](../explanation/terminology.md) §10 and the shape
in [`schema/install-manifest.schema.json`](../../schema/install-manifest.schema.json),
but two things were left to "decide when the installer ADR lands":

1. **Where the manifest is persisted** — file only, control DB row, or both.
2. **What it can contain in Phase 1**, given that per-app provisioning data
   (public URLs, database names, pinned versions) is produced by later slices
   (`SuiteEnv`, `DatabaseProvision`) that do not yet exist.

The audit trail already records the full chronological history
([ADR 0007](./0007-suite-audit-trail-before-after.md)); the manifest is the
**completion snapshot**, distinct from but aligned with the audit timeline
([`audit-trail.md`](../explanation/audit-trail.md) §8).

## Decision

1. **Persist the manifest as a row in the `nene_suite` control database**
   (`install_manifests` table), not a file, in Phase 1. A control-DB row is
   queryable, transactional with the completion mutation, and keeps orchestration
   metadata in one place ([ADR 0002](./0002-orchestrator-not-application-monolith.md)).
   A file export MAY be added later without changing this decision.

2. **`completeInstallSession` writes exactly one manifest** and links it from the
   session via `install_manifests.id` ↔ `install_sessions.install_manifest_id`.
   The completion records both `manifest.created` and `install_session.completed`
   audit events. The completed session's `after` snapshot carries
   `installManifestId`, satisfying audit-trail §8 alignment.

3. **Phase 1 minimal content.** The manifest body conforms to
   `install-manifest.schema.json` using only data available at completion:
   `suite_id`, `installed_at`, `org_external_id` (from `NENE_SUITE_ORG_EXTERNAL_ID`),
   `org_display_name`, `enabled_integrations` (`[]`), `app_versions` (`{}` until
   version pinning lands), and `disclaimer_accepted_at`. The `apps[]` array
   (`catalog_id`, `public_url`, `database_name`) is populated at completion
   (Issue #48) for each selected app with a configured `NENE_SUITE_APP_*_URL`:
   `public_url` from `SuiteEnv`, `database_name` derived by `DatabaseProvision`
   convention (catalog id hyphens → underscores). Apps without a configured URL
   are omitted; their selection stays auditable via `app_selection.changed`.

4. **No secrets** in the manifest body (R-05, audit-trail §5). A
   `manifest_content_hash` (SHA-256 of the canonical body) is stored for audit
   alignment.

5. **Table shape.** `install_manifests` stores `id` (ULID), `suite_id`,
   `content_json` (the schema-shaped body), `content_hash`, `created_at`. The
   manifest field names inside `content_json` stay snake_case per
   `schema-conventions.md`.

## Consequences

- `completeInstallSession` can satisfy R-05 now with an honest, schema-valid
  minimal manifest; richer `apps[]` content is additive later.
- Reviewers get a queryable completion record immediately.
- Follow-up: populate `apps[]` and `app_versions` when `DatabaseProvision` /
  `SuiteEnv` land; optional human-exportable file; `NENE_SUITE_CONTROL_DATABASE_URL`
  resolution remains a separate installer concern.

## Related

- Issue: `#36`
- Supersedes: none
- Superseded by: none

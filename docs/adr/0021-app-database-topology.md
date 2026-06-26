# ADR 0021: App Database Topology — Provision vs Adopt, Per-App Database Target

## Status

proposed (2026-06-26)

## Context

Today the suite provisions app databases with a single, **implicit** model:

- One shared MySQL/PostgreSQL server — the privileged provisioning connection
  `NENE_SUITE_PROVISION_DB_HOST` / `_PORT` / `_USER` / `_PASSWORD` / `_NAME`.
- Suite runs `CREATE DATABASE IF NOT EXISTS` (MySQL) / check-`pg_database`-then-create (PostgreSQL)
  **once per selected app** (`ProvisionAppDatabasesUseCase` → `PdoDatabaseProvisioner`).
- The database name is derived from the catalog id, hyphens→underscores (`AppDatabaseNamer`:
  `nene-invoice` → `nene_invoice`).
- The install manifest records `{catalog_id, public_url, database_name}` per app (ADR 0010).
- Each app then connects with its own `database.env_prefix`-scoped `*_DB_*` credentials; the suite
  never introduces a shared DSN (ADR 0004).
- Invariant: **one database per app, no shared application schema, no cross-database writes for domain
  data** (orchestration-compliance §3 — a P0 defect to violate; ADR 0002).

This single model derives one fixed shape — *same server, suite creates, name = catalog id* — and so
cannot express two real operator needs:

1. **Per-tool placement on a different server.** Some operators want a tool's database on a **separate
   MySQL/PostgreSQL instance** (blast-radius isolation, separate hosting, performance / quota), not
   only a separate database on the one shared server.
2. **Adopt an existing database.** A tool previously run **standalone** already has its own populated
   database (its own server, its own data). Bringing it under the suite must **register** that
   database — never `CREATE` a fresh one or touch its data. This is the **data-plane** counterpart of
   ADR 0012 §8 (standalone-first join / self-registration), which covers only the identity plane.

Constraints that stay invariant:

- orchestration-compliance §3 — one DB per app, no shared schema, no cross-DB domain writes, no "suite
  warehouse". P0.
- ADR 0002 — orchestrator, HTTP-only integration, each app owns its database.
- ADR 0004 — each app keeps its own DB credentials; the suite writes env, it does not own runtime creds.
- ADR 0011 / 0016 — the control DB and the provisioning connection are separate; the engine is derived
  from the control URL scheme; provisioning secrets are never persisted to manifest / audit / HTTP.

## Decision

### 1. A per-app **database target** replaces the implicit single model

Each installed app carries a **database target** descriptor (recorded in the install manifest,
ADR 0010):

- **mode** — `provision` (the suite `CREATE`s the database) | `adopt` (the suite **registers** an
  existing database and never creates or mutates it).
- **server** — which database server hosts it. Default = the suite provisioning server
  (`NENE_SUITE_PROVISION_DB_*`), preserving today's behavior; optionally a **per-app external server**.
- **database name** — provision: derived from the catalog id (`AppDatabaseNamer`, default unchanged);
  adopt: the existing database's name (operator-supplied).

The **default target** (`mode=provision`, server = suite provisioning server, name = catalog id)
reproduces **exactly today's behavior** — the common case is unchanged.

### 2. `provision` mode (default) — suite creates, idempotently

Unchanged in spirit from today: privileged `CREATE DATABASE IF NOT EXISTS` (MySQL) /
check-`pg_database`-then-create (PostgreSQL) on the **target** server, named from the catalog id. It
requires a privileged user **on that server** — for an external provision target, the privileged
connection is the **external server's**, not the suite's. The suite creates an **empty** database; the
app migrates its own schema on boot (ADR 0014 / its Tier A). The suite never runs the app's DDL.

### 3. `adopt` mode — register, never create or migrate

- The suite records the existing `{server, database name}` in the manifest and wires the app's env to
  point at it. It does **not** `CREATE`, drop, rename, or run any DDL/DML against the adopted database.
- Adopt needs **no privileged (CREATE) credential** — only the app's own connection credentials (the
  app uses/migrates its own schema). This is strictly weaker privilege than provision.
- Adopt is **non-destructive and idempotent**: re-running never overwrites data. If the target is
  unexpectedly empty or schema-incompatible, that is the app's own boot-migrate concern (ADR 0014),
  not the suite's.
- Adopt is the **data-plane half** of standalone-first join (ADR 0012 §8). The identity-plane half
  (link an existing local org/users by `external_id` / email) is ADR 0012; together they compose a
  full, non-destructive "bring an existing tool under the suite".

### 4. The compliance invariant holds across every mode and server

Regardless of mode or server: **one database per app, no shared application schema, no cross-database
writes for domain data** (orchestration-compliance §3). Many databases on one server (today's default)
and one database per external server are **both** fine; a **shared schema** is never. The suite still
writes no domain rows into any app database — `provision` makes an empty DB, `adopt` touches nothing,
and in both the app owns its tables.

### 5. Credentials & secrets

- The **provision** privileged connection is scoped to the **target** server, used at install time
  only, and never persisted to the manifest (ADR 0011 secrets policy).
- The app's own runtime `*_DB_*` credentials (catalog `database.env_prefix`) are written to its env,
  as today; the suite does not own them beyond writing.
- External-server and adopt credentials follow the same rule — **no secret** in the manifest, audit
  events, or HTTP responses (ADR 0007 / 0011).

## Open questions (resolve at acceptance)

- **OQ1 — how the database target is expressed.** Extend the env contract (e.g.
  `NENE_SUITE_APP_{SNAKE}_DB_*` for server/mode) vs an install-session input vs catalog metadata. Today
  only `NENE_SUITE_PROVISION_DB_*` (one server) + the per-app `NENE_SUITE_APP_{SNAKE}_URL` exist.
- **OQ2 — external-server privileged credentials for provision.** How an operator supplies a per-server
  privileged connection, and whether external-server *provision* ships in the Tier B MVP or
  **adopt-only first** for external servers (provision stays suite-server-only initially).
- **OQ3 — adopt entry point.** An operator action at install-session time vs a separate "register
  existing app" flow — this ties to ADR 0012 §8 self-registration (also unbuilt); ideally one flow.
- **OQ4 — manifest schema change.** Adding `mode` / `server` to manifest `apps[]` (currently
  `{catalog_id, public_url, database_name}`) — schema + terminology impact.
- **OQ5 — engine per app.** Today the engine is derived from the single control URL scheme (ADR 0016).
  An external server could run a different engine; is **heterogeneous-engine** support in scope, or is
  a single-engine constraint kept for now?

## Terminology registry impact (ADR 0006)

Proposed status coins **no** new canonical identifier yet. New identifiers — the mode values
(`provision` / `adopt`), any `NENE_SUITE_APP_{SNAKE}_DB_*` server/mode env vars, and manifest
`apps[].mode` / `apps[].server` fields — register **when their config shape is decided (OQ1 / OQ4) at
acceptance/implementation**. Reaffirm: `NENE_SUITE_PROVISION_DB_*` (terminology §4) stays the
**default-server** provisioning connection; the per-app DB env prefix stays `database.env_prefix`
(terminology §2.4).

## Consequences

**Benefits.** Expresses both real needs — per-tool server placement and adopt-existing — without
breaking the common case (the default target *is* today's behavior) and without weakening §3. Adopt
enables a **non-destructive** "bring an existing tool under the suite", the data-plane companion to
ADR 0012 §8.

**Costs / follow-up.** Manifest schema + env-contract additions (OQ1 / OQ4); the provisioner
generalized to a per-app target + per-server privileged connection; the adopt (register-only) path and
its safety tests; integration with ADR 0012 §8 self-registration.

**Risks.** Adopt pointed at the wrong or occupied database could mis-wire an app → mitigated by
**register-only** (no DDL), the app's own boot-migrate being non-destructive, and operator
confirmation. External *provision* needs a privileged credential for a server the suite does not own →
keep it explicit and audited; consider **adopt-only for external** in the MVP (OQ2).

## Related

- ADR 0002 (orchestrator, not monolith), orchestration-compliance §3 (DB separation — P0),
  ADR 0004 (env contract; the app owns its creds), ADR 0010 (install manifest `apps[]`),
  ADR 0011 (control vs provisioning DB; secrets policy), ADR 0016 (engine from URL scheme),
  ADR 0012 §8 (standalone-first join / self-registration — identity-plane companion to adopt),
  ADR 0014 (the sibling migrates its own schema on boot).
- Code: `src/DatabaseProvision/*` (`ProvisionAppDatabasesUseCase`, `AppDatabaseNamer`,
  `PdoDatabaseProvisioner`); `catalog/apps.json` `database.env_prefix`.
- Issue: `#277`. PR: `#000` (TBD).
- Superseded by: none.

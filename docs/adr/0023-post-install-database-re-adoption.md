# ADR 0023: Post-Install Database Re-Adoption and Sibling Preflight Contract

## Status

accepted (2026-06-26 — OQ1–5 resolved)

Extends **ADR 0021** (per-app database target / adopt) and **ADR 0022** (app onboarding modes) with a
**post-install** entry for changing an app's database target, and defines **who judges whether a
candidate database is legitimate** for an app. Builds on **ADR 0012** (§7 enrollment, §11 app-as-SSOT),
**ADR 0011 / ADR 0004** (secrets / env contract), **ADR 0019** (deployment-driven apply), and
**ADR 0010** (install manifest). Does not supersede any ADR. The sibling-side capability is a **generic
framework feature, never Suite-named** (recorded principle — NENE2 stays suite-agnostic).

## Context

ADR 0021/0022 deliver the database target (`provision` | `adopt`) and the **install-time** operator
entry: the install wizard's database step calls `setDatabaseTargets`, which is **install-session-gated**
(`status !== in_progress` → 409). After install there is **no surface** to view or change an app's
database target — and no way to **re-adopt** a different existing database for an already-installed app.

Adding such a post-install surface exposes a question install never had to answer cleanly: **is the
database the operator is pointing the app at actually the right database for that app?** An adopted DB
could be the wrong app's database, a wrong-version or foreign schema, an empty shell, **another
tenant's** data, or simply unreachable. Getting this wrong on a live app means corrupting or
mis-binding a customer's accounting data — a severe, hard-to-explain incident.

The orchestrator **cannot** answer this. The suite depends one-directionally on the sibling (Suite →
NENE2) and is **app-agnostic** — it does not know, and must not encode, any app's schema, migration
state, or domain invariants (ADR 0012 §11; the suite is the orchestrator, not the SSOT). Only the
**app** knows what a legitimate database for itself looks like. So legitimacy judgement must move to the
app, and the suite must stay an orchestrator and recorder.

Constraints (inherited, unchanged):

- The app is SSOT for its domain; the suite holds identity / roster only (ADR 0012 §11).
- Adopt is **register-only** — no DDL/DML on an adopted database (ADR 0021 §3); one database per app,
  no shared schema, no cross-DB writes.
- Runtime connection credentials live in the **app's own** `*_DB_*` env (`database.env_prefix`); they
  never enter the manifest, audit, or the wire between suite and app (ADR 0011 / ADR 0004).
- Applying a database change to a running app is **deployment-driven** — the app picks up new env on
  boot; the suite recreates the container, it does not hot-swap a live connection (ADR 0019).

## Decision

### 1. Legitimacy is the app's judgement; the suite orchestrates and records

The split is the load-bearing decision: **the app self-diagnoses** a candidate database and returns a
verdict; **the suite gates the re-adopt on that verdict, requires an explicit operator confirmation,
and records both** — and never inspects or judges the database itself. This preserves the one-way
dependency and keeps the suite app-agnostic.

### 2. Sibling-side generic preflight contract

The sibling framework exposes a **read-only** preflight operation, parallel to and reusing the auth of
the existing `/machine/health` (auth-gated, `X-NENE2-API-Key`). It is a **generic** "diagnose a
candidate database for this app" capability — it names no suite/orchestrator concept.

- **Input:** a **candidate profile id** only — *not* a DSN, server string, or credentials over the
  wire. The candidate connection is a profile the **operator pre-placed in the app's own env**
  (e.g. `…_DB_CANDIDATE_*`, an env allowlist). The app connects to the candidate with **its own**
  credentials and **read-only**, with a timeout and a bounded connection budget.
- **Output — a structured verdict** (no raw data; reason codes only):
  - `reachable` — could the app open a read-only connection.
  - `schema_recognized` — the candidate carries this framework's migration ledger (e.g. `phinx_log`).
  - `app_identity_match` — an app-identity marker (an id row the app writes at init) matches this app.
  - `tenant_match` — the candidate's org/tenant marker matches the expected federation org
    (`org_external_id`); mismatch is a hard stop.
  - `migration_state` — one of `fresh` (empty, ready to migrate) · `compatible` (at/behind this app's
    known migration head) · `ahead` (DB newer than the app — **dangerous**) · `foreign` (ledger of a
    different app / unknown) · `partial` (in-between — needs handling).
  - `populated` — empty vs has data.
  - `recommendation` — `adopt_safe` | `needs_migration` | `refuse`, plus `reason_codes`.
  - a content `fingerprint` and a short-lived signed `adoption_token` (see §5).
- **Auto-refuse** on `ahead`, `foreign`, or `tenant_match = false`.
- Framework shape: a generic `DatabaseAdoptionInspector` interface with a default implementation (most
  apps work unconfigured) and an app extension point. Defined and tracked as a **NENE2 framework
  feature** (cross-repo), never referencing the suite.

### 3. Credentials never cross the wire; candidate servers are allowlisted

The suite passes a **candidate profile id**, never a server string or credentials. The app reads the
candidate connection from its **own env allowlist** and connects itself. This (a) preserves ADR 0011
(creds stay app-side) and (b) closes **SSRF / arbitrary-connection**: the suite cannot make an app
connect to an attacker-chosen host, because only operator-placed env profiles are connectable.

### 4. Suite-side: a post-install operation and an Admin surface

- A new **install-session-independent** operation on the installed app —
  `PUT /api/v1/installed-apps/{catalogId}/database-target` — that (i) proxies the app's preflight,
  (ii) on `adopt_safe` and a valid `adoption_token`, records the new target, and (iii) audits.
- An **Admin → Databases** surface: read the current target (`mode`, `server` label, database name)
  per installed app, and run a **Re-adopt** flow (pick candidate → preflight → show verdict → strong
  confirm → confirm with `adoption_token`).
- **Apply is deployment-driven** (ADR 0019): the new target takes effect when the app is recreated /
  restarted with the new env. The suite does **not** hot-swap a live app's database connection.
- The **env path** (`NENE_SUITE_APP_{SNAKE}_DB_*`) remains a UI-less fallback.

### 5. TOCTOU: bind the diagnosis to the confirmation

Between preflight and confirmation the candidate database could change (time-of-check / time-of-use).
The preflight returns a content `fingerprint` and a **short-lived signed `adoption_token`**; the
suite's confirm operation **requires the token** and the app **re-verifies the fingerprint** at
confirm time. An expired token or a fingerprint mismatch refuses the change.

### 6. Responsibility model

A re-adopt is legitimate only when **both** hold: **(a)** the app's preflight verdict is `adopt_safe`,
and **(b)** the operator explicitly confirms. The suite **mediates and records**; it does not judge
the database. Apps that do not implement preflight return `unknown` → the suite **refuses by default**,
with an operator **override** that is itself audited (so the responsibility is explicit and recorded).

### 7. Invariants and safety

- Adopt stays **register-only / non-destructive** (ADR 0021 §3), so a wrong re-adopt does not damage
  the database and can be reverted by re-pointing. The **irreversible** risk is boot-time migration:
  when the verdict is `needs_migration`, the flow shows an explicit irreversibility warning and
  requires a backup-confirmation before proceeding.
- No secret in manifest / audit; `server` is a non-secret label (ADR 0011 / ADR 0021).
- New audit actions: **`database.preflight_evaluated`** (verdict + reason codes; no raw data) and
  **`database.readopted`** (the post-install target change; before/after target, operator, token id).

## Resolved at acceptance (2026-06-26)

- **OQ1 — candidate profile env naming → `NENE_SUITE_APP_{SNAKE}_DB_CANDIDATE_{SERVER,NAME}`.** Mirrors
  the active `NENE_SUITE_APP_{SNAKE}_DB_*` target keys (terminology §4.4) with a `_CANDIDATE_` infix the
  app reads read-only — no new shape to learn, and the env allowlist (§3) is simply "the candidate env
  exists."

- **OQ2 — `adoption_token` signing → per-app HMAC from the machine service credential, not federation
  JWKS.** The token flows **app → suite** (the app issues it after preflight; the suite verifies it at
  confirm). The federation JWKS is the suite's **outbound** assertion plane (suite → app, ADR 0012 B1) —
  the wrong direction. The machine credential (`X-NENE2-API-Key`) is already a suite↔app shared secret,
  so an HMAC the suite recomputes is the minimal, correctly-directed choice; JWKS stays login-only.

- **OQ3 — `tenant_match` key → the federation `org_external_id` (ADR 0012 §5).** The app stores the
  expected org marker (set at install / enrollment) and compares the candidate database's marker; a
  mismatch is a hard refuse. For **single-tenant OSS** installs (no federation org) `tenant_match` is
  reported `not_applicable` and does not gate the verdict.

- **OQ4 — preflight-unsupported policy → refuse-by-default with an audited operator override.** An app
  that does not implement preflight (or is below the minimum framework version, gated like the
  `/machine/health` version, NENE2#1414) returns `unknown` → the suite refuses; an operator may override,
  and `database.readopted` records `override: true` + the actor. Safe default, explicit accountability.

- **OQ5 — scope → read-only first.** The first slice ships **(a)** the sibling **preflight contract**
  (NENE2 generic) and **(b)** the Suite **Admin "Databases" read surface** (view each app's current
  target; run preflight to show a verdict) — **no write**. The actual re-adopt **write** of a running
  app (deployment-driven apply) is a **subsequent slice** gated behind this foundation. Rationale:
  changing a live app's database is high-stakes; shipping visibility + validation first delivers value
  at low risk and proves the preflight contract before any mutation.

## Terminology registry impact (ADR 0006)

Introduces identifiers — the preflight verdict fields, `migration_state` values, `adoption_token`, the
post-install operation, and the new audit actions — that **register with the implementation PRs**
(precedent: identifiers land with the PR that introduces them), reusing the ADR 0021 §4.4 database-target
vocabulary (`provision` / `adopt`, `NENE_SUITE_APP_{SNAKE}_DB_*`). **No `terminology.md` change at
proposal.**

## Consequences

**Benefits.** Post-install database management becomes possible **without** breaking the suite's
app-agnostic boundary: the app remains the sole judge of its own database's legitimacy, the suite stays
an orchestrator + recorder. Re-adopt is safe by construction — read-only diagnosis, credentials never
on the wire, SSRF closed by the env allowlist, TOCTOU closed by the token+fingerprint, register-only so
reversible, and a two-party (app + operator) accountable, audited decision.

**Costs / follow-up.** A **NENE2 generic preflight** capability (inspector interface + endpoint +
candidate-env convention; cross-repo; never Suite-named). A **Suite** post-install op + the Admin
Databases surface + audit. Deployment-driven apply wiring (ADR 0019).

**Risks.** Changing a live app's database is inherently high-stakes → mitigated by deployment-driven
apply (no hot-swap), the `needs_migration` irreversibility gate + backup confirmation, and OQ5's option
to ship read-only first. A self-reporting app is a trust surface → the app is already domain SSOT
(ADR 0012 §11); the verdict carries no raw data and the operator co-signs. Preflight-unsupported apps
are handled by OQ4 (refuse-by-default).

## Related

- **Extends**: ADR 0021 (per-app database target / adopt — adds the post-install entry + the legitimacy
  contract), ADR 0022 (app onboarding modes — this is the post-install counterpart to mode A's
  install-time entry; reuses the §2 decoupling so the target is not install-session-bound).
- **Builds on**: ADR 0012 (§7 enrollment auth reuse, §11 app-as-SSOT), ADR 0011 / ADR 0004 (secrets /
  env contract), ADR 0019 (deployment-driven apply), ADR 0010 (install manifest).
- Sibling side: a **generic** NENE2 framework capability ("diagnose a candidate database"), never
  Suite-named — filed as **NENE2#1419** (`/machine/database/preflight`, `DatabaseCandidateInspector`;
  Suite-unnamed; precedent: NENE2#1414 → #1417/#1418).
- Issue: `#303` (proposed), `#305` (accepted). PR: `#304` (proposed).
- Superseded by: none.

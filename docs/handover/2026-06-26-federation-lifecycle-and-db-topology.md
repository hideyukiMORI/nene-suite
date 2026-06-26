# Handover: Federation Lifecycle, App Database Topology & Onboarding (2026-06-26)

**Self-contained resume document.** Read this first if you are continuing the app-database /
onboarding work. It records everything this session shipped, every decision and its rationale, and a
**file-level plan for the immediate next task (mode A)** so a fresh session can execute without
re-discovering the codebase.

Foundation underneath (don't re-read unless needed): multi-tenant / federation
[`2026-06-22-multi-tenant-phase-a.md`](./2026-06-22-multi-tenant-phase-a.md); Origin / O6
[`2026-06-26-origin-and-o6-prerequisites.md`](./2026-06-26-origin-and-o6-prerequisites.md).
Operating rules: [`AGENTS.md`](../../AGENTS.md). Authoritative shipped record: `main` git log.

---

## 1. Where we are (one paragraph)

Three design contracts were accepted and one of them was implemented to completion. **ADR 0020**
(federated user lifecycle) and **ADR 0021** (app database topology) were accepted; **ADR 0021 was
implemented end to end** (the env-driven per-app database target + register-only **adopt** engine, and
the install manifest now records each app's target). The adopt **entry point** was then contracted as
**ADR 0022** (app onboarding modes), which splits it into **mode A** (suite-driven install adopt —
**PR1 backend (#292) + PR2 frontend (#296) landed — mode A complete**) and **mode B** (standalone-first
inbound join — deferred to B2).
Everything is backward-compatible (an unset target = today's provision behavior) and non-destructive
(adopt runs no DDL/DML). Gate: **PHPUnit 468 / vitest 72**, all green.

---

## 2. What shipped this session (all merged to `main`)

| # | What | Issue / PR |
| --- | --- | --- |
| 1 | **ADR 0020** Federated User Lifecycle — accepted | #275 / #276 |
| 2 | **ADR 0021** App Database Topology — accepted | #277 / #278 |
| 3 | **ADR 0021 impl ①** — env-driven target + adopt engine | #279 / #280 |
| 4 | Session handover doc (this file, first version) | #281 / #282 |
| 5 | **ADR 0021 impl ②** — install manifest records the target | #283 / #284 |
| 6 | doc-sync (impl ①② landed) | #285 / #286 |
| 7 | **ADR 0022** App Onboarding Modes — accepted | #287 / #288 |
| 8 | **ADR 0022 mode A impl PR1** — backend: session-carried target + layered resolver + `setDatabaseTargets` op | #291 / #292 |
| 9 | **ADR 0022 mode A impl PR2** — frontend: install-wizard `database` step calling `setDatabaseTargets` | #295 / #296 |

### 2.1 ADR 0021 implementation — what exists in code now

**impl ① (`src/DatabaseProvision/`)** — the per-app database target engine:

- `DatabaseTargetMode` (enum `provision` | `adopt`, `fromString`), `DatabaseTarget`
  (catalogId, mode, databaseName, `?server`; `isExternal()`).
- `DatabaseTargetResolverInterface` + `EnvDatabaseTargetResolver` — reads
  `NENE_SUITE_APP_{SNAKE}_DB_MODE` / `_SERVER` / `_NAME`; defaults to **provision, suite server,
  `AppDatabaseNamer` name** (= historical behavior). **Provision + external server throws**
  `ExternalProvisionNotSupportedException` (MVP: external is adopt-only, ADR 0021 OQ2). Validates the
  database name (`^[A-Za-z0-9_]{1,64}$`).
- `ProvisionAppDatabasesUseCase` is **resolver-driven**: provision → `CREATE` (as before); **adopt →
  register-only, no DDL/DML**, audits `database.adopted`. `ProvisionedAppDatabase` gained `mode` +
  `server`.
- Contracts: audit-trail §4 (`database.adopted` row; `database.provisioned` after_json gained `mode`),
  terminology §4.4 (`NENE_SUITE_APP_{SNAKE}_DB_*` + mode values), suite-environment-contract.md,
  `.env.suite.example`.

**impl ② (`src/InstallManifest/`, `src/InstallSession/`)** — the manifest records the target:

- `InstallManifestApp` gained `mode` + `?server`; `InstallManifestFactory` **omits them at their
  defaults** (provision/suite) so a default app entry stays byte-identical to
  `{catalog_id, public_url, database_name}`.
- `schema/install-manifest.schema.json` `apps[]` gained optional `mode` (enum) + `server` (string).
- `CompleteInstallSessionUseCase` now resolves the per-app `DatabaseTarget` via
  `DatabaseTargetResolverInterface` (was `AppDatabaseNamer` directly); `InstallSessionServiceProvider`
  wiring updated. terminology §10 gained `apps[].mode` / `apps[].server`.

**Net effect:** adopt already works **end to end via env** — set
`NENE_SUITE_APP_NENE_INVOICE_DB_MODE=adopt` + `_DB_SERVER` + `_DB_NAME` before a Tier B install and the
app is registered (not created) and recorded in the manifest. What's missing is only the **interactive
operator entry** (mode A, below).

---

## 3. Key decisions (and why) — do not re-litigate

- **ADR 0020 (federated user lifecycle).** Prompt deprovisioning beyond ADR 0012 §6 JIT-on-login.
  Two layers: **pull** lifecycle delta feed (SCIM-shaped, extends ADR 0012 §5 roster-pull) + best-effort
  **push** back-channel logout (OIDC-shaped, same JWKS as login). OQ1 push signed with federation JWKS;
  OQ2 ≤5 min detect + recommended session-TTL ceiling; OQ3 privilege reduction pushes / grant pulls;
  OQ4 delete = soft-disable, hard-purge → B6; OQ5 it's a **B2 follow-on**. No cross-DB writes; NENE2
  gets a generic SCIM/back-channel-logout feature, never Suite-named.
- **ADR 0021 (app database topology).** Per-app **database target** (`provision`|`adopt` + server).
  OQ1 env extension `NENE_SUITE_APP_{SNAKE}_DB_*`; OQ2 external = **adopt-only** in MVP; OQ3 adopt entry
  unified with ADR 0012 §8; OQ4 manifest gains `mode`/`server` omittable; OQ5 single-engine kept.
  Default reproduces today exactly; §3 invariant (one DB/app, no shared schema, no cross-DB writes)
  holds across all modes.
- **ADR 0022 (app onboarding modes).** The adopt entry = **one onboarding model, two entry modes**.
  **§2 decoupling (the crux):** the database target is **not install-session-exclusive** — both mode A
  (install-session) and mode B (inbound registration) feed the **same** provisioning + manifest path.
  OQ1 resolved: mode A uses a **dedicated `PUT /api/v1/install-sessions/{id}/database-targets`** op
  (not an `updateAppSelection` extension). OQ2–4 (registration entity, identity reconciliation,
  provenance/trust + §7 enrollment security) deferred to **B2**.
- **Sequencing decision (why mode B's contract came before mode A's code).** Both modes are needed.
  Implementing both at once is impossible (mode B needs B2 + unbuilt §7/§8, partly cross-repo). Building
  mode A alone risked welding the target to the install-session, which mode B (no install-session) can't
  reuse → rework. So we fixed **mode B's contract** (ADR 0022) to the level that constrains mode A,
  then unblocked mode A. The "do it all at once" safety is bought at the **contract** level.

---

## 4. NEXT TASK — implement mode A (suite-driven install adopt)

ADR 0022 §3 + OQ1. Goal: let an operator choose `provision`/`adopt` per app (and supply
`{server, name}` for adopt) through the install wizard / API / CLI, instead of hand-editing env.

> **Update (2026-06-26): PR1 (backend, §4.2) landed as #292** (reviewed + merged). The session now
> carries `databaseTargets` (`AppDatabaseTargetSelection`); resolution is layered via
> `SessionDatabaseTargetResolver` (session override → env → default), with validation shared in the
> extracted `DatabaseTargetFactory`; and the op **`setDatabaseTargets`**
> (`PUT …/install-sessions/{id}/database-targets`, `src/DatabaseTargets/`) is live, with audit
> `database_targets.configured` and the `database_targets_json` column. **PR2 (frontend, §4.3) then
> landed as #296** — the install wizard has a `database` step (per-app provision/adopt + adopt
> server/name) that calls `setDatabaseTargets`. **Mode A is now complete end to end; the remaining
> onboarding work is §5 (deferred): mode B, ADR 0020 impl, B2.** Gate: PHPUnit 468 / vitest 72.

### 4.1 The install flow (map — this is what `/clear` erases, so it's captured here)

- **CLI** `installer/install.php` → `src/Installer/InstallerUseCase.php`, **8 ordered steps**:
  1 StartInstallSession · 2 UpdateAppSelection (dependency-resolved) · 3 AcceptDisclaimer ·
  **4 ProvisionAppDatabases** · 5 CreateOperator · 6 BootstrapDefaultOrganization ·
  **7 WriteEnvConfig** · **8 CompleteInstallSession** (writes manifest).
  Config comes from env (`InstallerEnvReader`): `NENE_SUITE_INSTALLED_APPS`, etc.
  **CRITICAL ORDERING:** provisioning (step 4) runs **before** WriteEnvConfig (step 7). So you
  **cannot** "collect target in the wizard → write env → resolve" within one run — the target must be
  **carried on the install-session** and consulted at steps 4 and 8.
- **HTTP install-session ops** (`docs/openapi/openapi.yaml`): `startInstallSession`,
  `getInstallSession`, `updateAppSelection` (`PUT …/app-selection`), `acceptDisclaimer`,
  `completeInstallSession`, `failInstallSession`.
- `updateAppSelection` → `src/AppSelection/UpdateAppSelectionUseCase.php` /
  `UpdateAppSelectionInput` (carries `installSessionId`, `selectedApps: list<string>`, `requestId`;
  dependency-resolved server-side). **Leave this unchanged** (OQ1 chose a separate op).
- `src/InstallSession/InstallSession.php` — `selectedApps` is a **flat `list<string>`** of catalog ids;
  there is no per-app metadata today (this is what mode A adds).
- **Resolver is env-only today:** `DatabaseTargetResolverInterface::resolve(catalogId)` →
  `EnvDatabaseTargetResolver`, consumed by `ProvisionAppDatabasesUseCase` and
  `CompleteInstallSessionUseCase`. There is no session/HTTP path to a target yet.
- **Frontend wizard** `frontend/src/features/install-wizard/`: `STEP_ORDER =
  ['apps','disclaimer','review','complete']` (`ui/InstallWizard.tsx`); `steps/AppSelectionStep.tsx`
  collects `string[]`; `hooks/use-install-wizard.ts` drives the steps. No per-app DB UI yet.

### 4.2 Backend plan (PR 1 — Suite-contained, no cross-repo) — ✅ landed (#292)

1. **Domain:** a per-app target override carried on `InstallSession` (e.g. a value object
   `AppDatabaseTargetSelection { catalogId, mode, ?server, ?name }` + a map / list on the session, plus
   `InstallSession::withDatabaseTargets(...)`). Keep it optional (default empty = today's behavior).
2. **Layered resolution — session override → env → default.** The current `resolve(catalogId)` is
   context-free. Recommended approach: a resolver/decorator that, given the session's overrides, returns
   the override's `DatabaseTarget` when present, else delegates to the env `EnvDatabaseTargetResolver`.
   **Reuse the same validation** (mode enum, external = adopt-only, safe name) on session-supplied
   values — factor the validation out of `EnvDatabaseTargetResolver` so both paths share it. Wire
   `ProvisionAppDatabasesUseCase` (step 4) and `CompleteInstallSessionUseCase` (step 8) to consult the
   session overrides.
3. **API:** new op **`PUT /api/v1/install-sessions/{installSessionId}/database-targets`** in
   `docs/openapi/openapi.yaml` (request: per-app `{ catalogId, mode, server?, name? }` for the resolved
   app set) + route + handler + a `SetDatabaseTargets` use case + audit (a new
   `database_targets.configured` action under entity `app_database` — register in audit-trail §4 +
   it matches the schema's free `action` pattern; entity `app_database` already enumerated). Validation
   reused; `provision` + external `server` → 422 (ADR 0021 OQ2). Apps with no entry default to provision.
4. **Tests:** the session VO, the layered resolver (override wins / falls back / validates), the new op
   handler, and that Provision + CompleteInstallSession honor an adopt override.

### 4.3 Frontend plan (PR 2) — ✅ landed (#296)

- Add a `database` step: `STEP_ORDER = ['apps','database','disclaimer','review','complete']`.
- `steps/DatabaseStep.tsx`: per selected app, choose provision/adopt; adopt → `server` + existing
  `name` inputs; submit to the new endpoint via the wizard hook.
- **`npm run codegen`** after the openapi change → commit `frontend/src/shared/api/schema.gen.ts`
  (this is a **Frontend CI gate** — stale generated types fail CI; see §6).
- vitest + MSW for the step.

### 4.4 Suggested slicing

PR 1 = backend (domain + layered resolver + API + wiring + tests). PR 2 = frontend (wizard step +
codegen + tests). Both behavior-preserving for the default (provision) case.

---

## 5. Later work (deferred, dependency-ordered — not startable now)

- **Mode B — standalone-first inbound join** (ADR 0022 OQ2–4): §7 enrollment (one-time token →
  credential exchange) + §8 inbound self-registration (externally-installed app registers inbound with
  its own descriptor) + identity linking (existing org by `external_id`, users by `email`). **Depends on
  B2.** Sibling side = a **generic** "join an upstream hub" framework feature (never Suite-named —
  lesson: NENE2#1414 → #1417/#1418).
- **ADR 0020 implementation — federated user lifecycle:** the pull lifecycle delta feed + back-channel
  logout sender (Suite) and the NENE2 generic SCIM/back-channel-logout feature. **Depends on B2 + the
  ADR 0012 §5 roster-pull surface.** File the NENE2 generic issue only once B2 is in view.
- **B2 itself** — sibling-side org resolution + authorization-code assertion flow (cross-repo).
- **Cross-repo sibling adoption** for live installed-version data: nene-invoice#496 / nene-clear#182 /
  nene-records#586 (from the Origin/O6 handover; not Suite-blocking).

---

## 6. Process, gate & gotchas (read before coding)

- **Governance:** issue-driven; branch `type/issue-number-summary`; Conventional Commits (English
  `type`/`scope`, **Japanese** description/body, `(#issue)` in subject); `main` is protected
  (`protect-main`: PR + CI required, **no admin bypass**, no direct push). **Squash-merge**, delete
  branch. End commits with the `Co-Authored-By: Claude …` trailer.
- **Backend gate** (`composer check`): `test` (PHPUnit) · `analyse` (PHPStan, must be clean) ·
  `cs` (php-cs-fixer; `composer cs:fix` to auto-fix) · `openapi` (validate-openapi) ·
  `schema:docs:check`. **Frontend gate:** type-check + vitest + **codegen freshness**.
- **Local PHPUnit caveat:** a polluting local `.env` makes
  `ControlDatabaseConfigResolverTest::testFallsBackToConfigLoaderWhenUnset` fail locally only. Confirm
  by moving `.env` aside (`mv .env .env.bak && phpunit … && mv .env.bak .env`); **CI is green**. Don't
  chase it.
- **OpenAPI codegen freshness (RELEVANT to mode A):** any `docs/openapi/openapi.yaml` change requires
  `cd frontend && npm run codegen` and committing the regenerated `schema.gen.ts`, or the Frontend CI
  job fails.
- **Terminology:** `tools/check-terminology.sh` is a **forbidden-pattern** scanner (doesn't enforce
  "registered or fail"), but ADR 0006 policy still requires registering new identifiers in
  `docs/explanation/terminology.md` in the same PR. **ADR 0018 precedent:** register identifiers with
  the implementation PR that introduces them (which is mode A's PRs for the new op fields).
- **Docs link check:** `tools/check-links.sh` validates relative links; run after doc edits.
- **NENE2 stays Suite-agnostic:** any sibling-side request (mode B) must be a generic framework feature,
  never naming the suite/orchestrator.

---

## 7. Pointers

- ADRs: [`../adr/0020-federated-user-lifecycle.md`](../adr/0020-federated-user-lifecycle.md),
  [`../adr/0021-app-database-topology.md`](../adr/0021-app-database-topology.md),
  [`../adr/0022-app-onboarding-modes.md`](../adr/0022-app-onboarding-modes.md),
  [`../adr/0012-federation-participation-contract.md`](../adr/0012-federation-participation-contract.md).
- Code (ADR 0021): `src/DatabaseProvision/`, `src/InstallManifest/`,
  `src/InstallSession/CompleteInstallSessionUseCase.php`.
- Install flow: `installer/install.php`, `src/Installer/InstallerUseCase.php`,
  `src/AppSelection/`, `src/InstallSession/`, `frontend/src/features/install-wizard/`.
- Contracts: `docs/explanation/terminology.md` (§4.4, §10), `docs/explanation/audit-trail.md` §4,
  `docs/explanation/suite-environment-contract.md`, `schema/install-manifest.schema.json`.
- Status: [`../todo/current.md`](../todo/current.md), [`../roadmap.md`](../roadmap.md).

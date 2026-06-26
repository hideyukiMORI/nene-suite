# Handover: ADR 0022 Mode A — Implementation Complete (2026-06-26)

**Self-contained session record + resume document.** This session implemented **ADR 0022 mode A**
(suite-driven install adopt) **end to end** — backend, frontend, contracts, tests — all merged to
`main`. An operator can now choose `provision`/`adopt` per app (adopt → server / name) through the
install wizard; the choice rides on the install session and is honoured when databases are
provisioned and when the install manifest is written.

Design background — the ADR rationale and the original mode A *plan* — lives in
[`2026-06-26-federation-lifecycle-and-db-topology.md`](./2026-06-26-federation-lifecycle-and-db-topology.md)
(don't re-read unless you need the *why*; this doc is the *what shipped* + *what's next*). ADRs:
[`0021`](../adr/0021-app-database-topology.md), [`0022`](../adr/0022-app-onboarding-modes.md).
Operating rules: [`AGENTS.md`](../../AGENTS.md). Authoritative shipped record: `main` git log.

---

## 1. What shipped (実績)

**Mode A is complete end to end.** Four PRs, each independently code-reviewed, CI-green, and
squash-merged to protected `main`:

| # | What | Issue / PR |
| --- | --- | --- |
| 1 | **Backend** — install-session-carried target + layered resolver + `setDatabaseTargets` op + persistence + contracts | #291 / #292 |
| 2 | doc-sync (PR1 landed) | #293 / #294 |
| 3 | **Frontend** — install-wizard `database` step calling `setDatabaseTargets` | #295 / #296 |
| 4 | doc-sync (PR2 landed = mode A complete) | #297 / #298 |

Gate at close: **PHPUnit 468 / vitest 72**, all green. Default (`provision` on the suite server) is
behaviour-preserving and byte-identical to before; everything new is additive and non-destructive
(`adopt` runs no DDL/DML).

---

## 2. What exists in code now

### 2.1 Backend (#292)

- **`src/InstallSession/AppDatabaseTargetSelection.php`** — the operator's per-app override value
  object `{ catalogId, mode, ?server, ?name }`. Carried on `InstallSession` via a new
  `databaseTargets` field (default `[]` = today's behaviour), plus `databaseTargetFor(catalogId)`
  and `withDatabaseTargets(...)`. Holds no secrets.
- **`src/DatabaseProvision/DatabaseTargetFactory.php`** — validation **extracted** from
  `EnvDatabaseTargetResolver`: mode/name defaulting (provision → convention name; adopt → supplied
  name or convention), safe-name charset, and `provision` + external server → refused (ADR 0021
  OQ2). Shared by **both** the env path and the session path, so a wizard-supplied target is
  validated identically to an env one.
- **`src/DatabaseProvision/SessionDatabaseTargetResolver.php`** — layered resolution: **session
  override → env fallback → default**. `ProvisionAppDatabasesUseCase` (install step 4) and
  `CompleteInstallSessionUseCase` (step 8) now call `resolve($session, $catalogId)`.
  `EnvDatabaseTargetResolver` is the injected fallback.
- **`src/DatabaseTargets/`** — the new op module (parallel to `AppSelection`):
  `SetDatabaseTargets{Input,Output,UseCase,UseCaseInterface,Handler}`, `DatabaseTargetsRouteRegistrar`,
  `DatabaseTargetsServiceProvider`. **`PUT /api/v1/install-sessions/{installSessionId}/database-targets`**
  (operationId `setDatabaseTargets`). Validates each target via the factory and rejects with **422**:
  `provision` + external server, unsafe name, an app not in the session's `selectedApps`, a duplicate
  catalog id. Records audit **`database_targets.configured`** (entity `app_database`, `entity_id` =
  session id). Registered in `ApplicationServiceProvider`.
- **Persistence**: `install_sessions.database_targets_json` — phinx migration
  `database/migrations/20260626000100_add_database_targets_to_install_sessions.php` (production path)
  **and** `database/schema/install_sessions.sql` (the flat SQL the `PdoInstallSessionRepository`
  test loads). **Both must stay in sync.** Column is nullable (no default — TEXT-default portability
  across MySQL/PgSQL/SQLite); repo encodes/decodes a JSON list.
- **Contracts**: `docs/openapi/openapi.yaml` (new path + op + `SetDatabaseTargetsRequest` /
  `DatabaseTargetSelection` / `DatabaseTargetMode` schemas + `InstallSession.databaseTargets`, marked
  **required** — it's always emitted, like `selectedApps`); `docs/explanation/audit-trail.md` §4;
  `docs/explanation/terminology.md` §4.4.

### 2.2 Frontend (#296)

- **`frontend/src/entities/install-session/`** — `useSetDatabaseTargets` mutation (same
  `useSessionWriter` pattern as the others), `DatabaseTargetInput` / `SetDatabaseTargetsInput`, and
  the `SetDatabaseTargetsRequest` / `DatabaseTargetSelection` DTOs (from `schema.gen.ts`).
- **`frontend/src/features/install-wizard/ui/steps/DatabaseStep.tsx`** — per selected app, a
  provision/adopt `<select>`; adopt reveals optional `server` / `name` inputs (empty = suite server /
  convention). Submits an entry for **every** selected app (explicit + auditable; provision entries
  are harmless). `Next` is disabled while `apps` is empty (e.g. the session is still loading) —
  mirrors `AppSelectionStep`.
- **Wizard flow**: `STEP_ORDER = ['apps','database','disclaimer','review','complete']`;
  `use-install-wizard` adds the `setDatabaseTargets` action (`apps → database → disclaimer`).
- i18n `suite.install.database.*` in **en + ja** (other 4 locales fall back to en, per the i18n
  posture); MSW `PUT …/database-targets` handler; tests (wizard flow + `DatabaseStep` 4 cases).

---

## 3. Key decisions (and why)

- **Target rides on the install session, not env-then-resolve.** In the installer's 8-step flow,
  provisioning (step 4) runs **before** `WriteEnvConfig` (step 7), so the target can't be "collected
  → written to env → resolved" in one run; it must be carried on the session and consulted at steps
  4 and 8. (ADR 0022 §2: the target is **not** install-session-exclusive — both mode A and mode B
  feed the same provisioning + manifest path, which is why mode A could ship without welding the
  target to the session shape in a way mode B can't reuse.)
- **Validation extracted to `DatabaseTargetFactory`** so the session and env paths apply identical
  guards. `provision` + external is refused at **configure time** (422), not as a 500 at provision
  time.
- **A dedicated `setDatabaseTargets` op** (ADR 0022 OQ1), not an `updateAppSelection` extension.
- **The frontend sends every selected app's choice** (not just adopt overrides). Explicit and the
  audit records each app's decision; provision entries resolve to provision anyway.
- **`databaseTargets` is `required` in the openapi `InstallSession` schema** (a code-review finding):
  the view always emits it as an array, exactly like `selectedApps`, so the contract + the 6 response
  examples + `schema.gen.ts` were tightened to match.

---

## 4. Process notes / gotchas (read before the next contract or frontend change)

- **OpenAPI codegen freshness.** Any `docs/openapi/openapi.yaml` change requires
  `cd frontend && npm run codegen` + committing `frontend/src/shared/api/schema.gen.ts` — **even
  when the consuming UI is a later PR**. Stale generated types fail Frontend CI. (Tripped on PR1.)
- **The frontend gate is `npm run check`**, which is
  `type-check && lint && **prettier --check (format)** && vitest && **vite build**`. Run the **whole**
  thing locally before pushing — the individual scripts miss `format` and `build`. (Tripped on PR2;
  the design handover §6 under-described this.) Recorded in agent memory.
- **Local PHPUnit `.env` caveat.** `ControlDatabaseConfigResolverTest` fails **locally only** under a
  polluting `.env`; run backend tests with `mv .env .env.bak && composer test && mv .env.bak .env`.
  CI is green regardless.
- **Two schema sources for the control DB.** A column change needs **both** the phinx migration
  (production) and `database/schema/*.sql` (the flat SQL the Pdo repo tests load), then
  `composer schema:docs` to regenerate `docs/reference/schema.md` (the `schema:docs:check` gate).
- **Review before merging to protected `main`.** Each feature PR got an independent multi-angle
  code review (correctness / cross-file / conventions) before merge; PR2's review caught the
  empty-apps `Next` guard. Cheap insurance for protected-main merges.
- **Governance**: issue-driven; branch `type/issue-number-summary`; Conventional Commits (English
  `type`/`scope`, Japanese body, `(#issue)` in subject); **squash-merge** + delete branch;
  `Co-Authored-By: Claude …` trailer. `main` is protected (`protect-main`: PR + CI required, no admin
  bypass).

---

## 5. Next (deferred — not startable now)

**Mode A is done.** The remaining onboarding work is **B2-blocked** (see the design handover §5):

- **Mode B — standalone-first inbound join** (ADR 0022 OQ2–4): §7 enrollment (one-time token →
  credential exchange) + §8 inbound self-registration + identity linking (org by `external_id`,
  users by `email`). **Depends on B2.** Sibling side = a **generic** "join an upstream hub" framework
  feature, never Suite-named (lesson: NENE2#1414 → #1417/#1418).
- **ADR 0020 implementation — federated user lifecycle**: the pull lifecycle delta feed + best-effort
  back-channel logout sender (Suite) + the NENE2 generic SCIM / back-channel-logout feature.
  **Depends on B2 + the ADR 0012 §5 roster-pull surface.**
- **B2 itself** — sibling-side org resolution + authorization-code assertion flow (cross-repo).

**Unblocked alternative** (if you want to keep moving without B2): **epic #251** — the Suite
**deployment-driven** upgrade orchestrator (dependency-ordered image recreate + min-version gating;
the sibling migrates on boot) + apex "update all" UI (ADR 0019). Prerequisites ①–④ are landed.

---

## 6. Pointers

- Backend code: `src/DatabaseTargets/`, `src/DatabaseProvision/{DatabaseTargetFactory,SessionDatabaseTargetResolver}.php`,
  `src/InstallSession/AppDatabaseTargetSelection.php`,
  `src/InstallSession/CompleteInstallSessionUseCase.php`, `src/DatabaseProvision/ProvisionAppDatabasesUseCase.php`.
- Frontend code: `frontend/src/features/install-wizard/` (`ui/steps/DatabaseStep.tsx`,
  `hooks/use-install-wizard.ts`, `ui/InstallWizard.tsx`), `frontend/src/entities/install-session/`.
- Contracts: `docs/openapi/openapi.yaml`, `docs/explanation/terminology.md` (§4.4),
  `docs/explanation/audit-trail.md` (§4), `database/migrations/`, `database/schema/install_sessions.sql`.
- Design background + decisions: [`2026-06-26-federation-lifecycle-and-db-topology.md`](./2026-06-26-federation-lifecycle-and-db-topology.md)
  (§3 decisions, §4 plan), [`ADR 0022`](../adr/0022-app-onboarding-modes.md).
- Status / roadmap: [`../todo/current.md`](../todo/current.md).

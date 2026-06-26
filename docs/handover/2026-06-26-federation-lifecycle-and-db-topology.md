# Handover: Federated User Lifecycle & App Database Topology (2026-06-26)

Status record and handover for two design decisions accepted this session — **federated user
lifecycle** (ADR 0020) and **app database topology** (ADR 0021) — and the **first implementation
slice of ADR 0021** (the env-driven per-app database target + adopt engine). This is the entry point
for whoever continues the database-topology implementation or the federation lifecycle build-out.

These threads sit on the multi-tenant / federation foundation; for that, see
[`2026-06-22-multi-tenant-phase-a.md`](./2026-06-22-multi-tenant-phase-a.md) and the Origin / O6
handover [`2026-06-26-origin-and-o6-prerequisites.md`](./2026-06-26-origin-and-o6-prerequisites.md).

Operating rules: [`AGENTS.md`](../../AGENTS.md). Driving ADRs: **0020** (federated user lifecycle —
extends **0012**), **0021** (app database topology). Authoritative shipped record: `main` git log.

---

## 1. Where we are

- **ADR 0020 — Federated User Lifecycle: accepted.** Prompt deprovisioning beyond ADR 0012 §6
  JIT-on-login (the lazy/positive half). Two-layer propagation — **pull** lifecycle delta feed
  (SCIM-shaped, the user-grain extension of ADR 0012 §5 roster-pull) + best-effort **push** back-channel
  logout (OIDC-shaped, verified via the same JWKS as login). No cross-DB writes; NENE2 gets a *generic*
  framework feature, never Suite-named. **No code yet** — it is a **B2 follow-on** (depends on B1 keys
  [landed] + B2 org resolution + the roster-pull surface).
- **ADR 0021 — App Database Topology: accepted.** The implicit single model (same server, suite
  `CREATE`s, catalog-id name) generalized to a per-app **database target** (`provision` | `adopt` +
  configurable server). The default reproduces today's behavior exactly; the MVP adds external-server
  **adopt** (register an existing DB, no DDL) — the data-plane companion to ADR 0012 §8.
- **ADR 0021 implementation ① + ② — landed; ADR 0021 is functionally complete.** ① the env-driven
  target model + adopt-aware provisioning engine; ② the install manifest records each app's
  `mode` / `server` and `CompleteInstallSessionUseCase` resolves the target via the resolver. Both on
  `main`, behavior-preserving and fully tested. The remaining ADR 0021 work is the **operator-facing
  adopt entry-point flow** (OQ3), tied to the unbuilt ADR 0012 §8 self-registration — a separate task.
- Everything is **non-destructive and backward-compatible**: an unset target = today's provision
  behavior; adopt never runs DDL/DML; the §3 invariant (one DB per app, no shared schema, no cross-DB
  writes) holds across every mode and server.
- Gate state: **PHPUnit 445 / vitest 68**, all green (the local-only `.env`-pollution flake on
  `ControlDatabaseConfigResolverTest` is unrelated and green in CI — see
  the local phpunit caveat in the team notes).

---

## 2. What shipped (merged to `main`)

| Area | What | Issue / PR |
| --- | --- | --- |
| ADR 0020 | Federated User Lifecycle — proposed → **accepted** (OQ1–5 resolved); extends ADR 0012; no terminology change | #275 / #276 |
| ADR 0021 | App Database Topology — proposed → **accepted** (OQ1–5 resolved) | #277 / #278 |
| ADR 0021 impl ① | env-driven `DatabaseTarget` + `DatabaseTargetMode` + `EnvDatabaseTargetResolver`; `ProvisionAppDatabasesUseCase` resolver-driven (provision = `CREATE`, **adopt = register-only** + audit `database.adopted`); audit-trail §4, terminology §4.4, env-contract, `.env.suite.example` | #279 / #280 |
| ADR 0021 impl ② | install manifest records the target — `InstallManifestApp` + factory + schema gain `mode` / `server` (omitted at defaults = byte-identical); `CompleteInstallSessionUseCase` resolves the target via `DatabaseTargetResolverInterface`; terminology §10 | #283 / #284 |
| ADR 0022 | App Onboarding Modes — proposed → **accepted** (OQ1 resolved; mode B OQ2–4 deferred to B2). Contracts the adopt entry as one model / two modes; decouples the target from the install-session so mode A is forward-compatible with mode B | #287 / #288 |

No cross-repo (NENE2) requests were filed this session — by design, the NENE2 generic features for
both ADRs wait until B2 is in view (see §4).

---

## 3. Key decisions (OQ resolutions)

**ADR 0020 (federated user lifecycle):**

- **OQ1** push logout token signed with the **federation JWKS key** (same trust root as login; no new
  key type). Pull feed authed with the ADR 0012 §7 enrollment service credential.
- **OQ2** SLA: push immediate; push-unavailable → pull detects within **≤ 5 min** for new authz; existing
  sibling sessions bounded by a **recommended** suite-mode access-token TTL (≈ ≤ 15 min) + refresh-time
  re-validation (the sibling owns its session, so it is a recommendation, not a mandate).
- **OQ3** privilege **reduction → push** (fail-safe); **grant → pull-lazy**.
- **OQ4** delete = **soft-disable only**; hard-purge is a separate audited op routed to **B6** legal review.
- **OQ5** **B2 follow-on** (needs B1 keys + B2 org resolution + ADR 0012 §5 roster-pull surface).

**ADR 0021 (app database topology):**

- **OQ1** target via env extension `NENE_SUITE_APP_{SNAKE}_DB_*` (`_MODE` / `_SERVER` / `_NAME`).
- **OQ2** **external server = adopt-only** in the Tier B MVP; external *provision* deferred (least
  privilege — the suite holds no CREATE credential for a server it does not own).
- **OQ3** adopt entry point **unified with ADR 0012 §8 self-registration** (data plane + identity plane
  in one "register an existing app" flow).
- **OQ4** manifest `apps[]` gains `mode` (default `provision`) + `server` (default suite), omittable.
- **OQ5** **single-engine** kept (engine from the control URL scheme, ADR 0016); heterogeneous deferred.

Both ADRs coin identifiers lazily: terminology rows register **with the implementation PR that builds
the surface** (ADR 0018 precedent). ADR 0021 impl ① therefore registered the env pattern + mode values;
manifest fields register with impl ②.

---

## 4. Remaining tasks

**ADR 0021 implementation ① + ② — done (#280, #284).** The engine + the manifest surface are on
`main`. What remains for ADR 0021 is only the operator-facing adopt entry-point (below).

**Immediate — adopt entry-point: mode A (contracted by ADR 0022, accepted):**

- The adopt entry is now contracted as **ADR 0022 (App Onboarding Modes)** — one onboarding model with
  two entry modes. **Mode A** (suite-driven install adopt) is unblocked and is the next implementation:
  a dedicated **`PUT /api/v1/install-sessions/{id}/database-targets`** operation carrying per-app
  `{mode, server, name}`, resolved **session → env → default**, plus a wizard `database` step
  (`apps → database → disclaimer → review`). The database target is **decoupled from the install-session**
  (ADR 0022 §2) so mode B reuses the same path — this is what removes the A→B rework risk.
- **Mode B** (standalone-first inbound join — ADR 0012 §7 enrollment + §8 self-registration + identity
  linking) stays deferred to **B2**; its specifics are ADR 0022 OQ2–OQ4. The sibling side is a generic
  "join an upstream hub" framework feature (never Suite-named).

**Later — not blocking, dependency-ordered:**
- **ADR 0020 implementation** (federated user lifecycle) — the pull lifecycle delta feed + back-channel
  logout sender (Suite) and the NENE2 *generic* SCIM/back-channel-logout feature. Depends on **B2** (org
  resolution) + the **ADR 0012 §5 roster-pull surface** (both unbuilt). File the NENE2 generic feature
  issue only once B2 is in view — never name the suite (lesson: NENE2#1414 → #1417/#1418).
- **B2** itself — sibling-side org resolution + authorization-code assertion flow (cross-repo).
- Cross-repo sibling adoption for live installed-version data: nene-invoice#496 / nene-clear#182 /
  nene-records#586 (from the prior Origin/O6 handover; not Suite-blocking).

---

## 5. Pointers

- ADRs: [`../adr/0020-federated-user-lifecycle.md`](../adr/0020-federated-user-lifecycle.md),
  [`../adr/0021-app-database-topology.md`](../adr/0021-app-database-topology.md),
  [`../adr/0012-federation-participation-contract.md`](../adr/0012-federation-participation-contract.md).
- Code (ADR 0021 impl ①): `src/DatabaseProvision/` — `DatabaseTarget`, `DatabaseTargetMode`,
  `DatabaseTargetResolverInterface`, `EnvDatabaseTargetResolver`, `ExternalProvisionNotSupportedException`,
  `ProvisionAppDatabasesUseCase`.
- Contracts touched: `docs/explanation/audit-trail.md` §4, `docs/explanation/terminology.md` §4.4,
  `docs/explanation/suite-environment-contract.md`, `.env.suite.example`.
- Current work / roadmap: [`../todo/current.md`](../todo/current.md), [`../roadmap.md`](../roadmap.md).

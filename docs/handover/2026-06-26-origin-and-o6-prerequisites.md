# Handover: Origin Consumption & O6 Upgrade-Orchestration Prerequisites (2026-06-26)

Status record and handover for the **Origin consumption client** and the **O6 upgrade-orchestration
prerequisites**, built on the multi-tenant foundation. This is the single entry point for whoever
continues the upgrade-orchestration work. For the multi-tenancy / federation foundation underneath,
see the prior handover: [`2026-06-22-multi-tenant-phase-a.md`](./2026-06-22-multi-tenant-phase-a.md).

Governing plan: [`../milestones/2026-06-origin-and-upgrade-orchestration.md`](../milestones/2026-06-origin-and-upgrade-orchestration.md).
Operating rules: [`AGENTS.md`](../../AGENTS.md). Driving ADRs: **0017** (Origin consumption),
**0013** (update aggregation — accepted), **0019** (Tier B deployment-driven upgrade — proposed;
supersedes **0018**).

---

## 1. Where we are

- **Origin consumption client — complete** (epic #230): the apex shell shows verified updates /
  announcements / house-ads, or honest placeholders when Origin is unconfigured.
- **O6 upgrade-orchestration prerequisites ①–④ — complete / accepted.** ① installed-version tracking,
  ② catalog version mirror, ③ ADR 0013 accepted, ④ upgrade contract (ADR 0018 → superseded by ADR
  0019).
- **ADR 0019 (deployment-driven) is `proposed`.** Its acceptance — resolving three open questions —
  is the **next gate** before any O6 implementation.
- Everything shipped is **non-destructive and defensive**: Origin disabled or a version unreported →
  `unknown` / placeholders, never fabricated data. No upgrade *apply* code exists yet (that is O6).
- Gate state: **PHPUnit 431 / vitest 68**, all green.

---

## 2. What shipped (merged to `main`)

| Area | What | PRs |
| --- | --- | --- |
| Origin client | profiled-TUF read model (verify + corpus parity + watermark + 3 read APIs + dashboard) | #232–#250 (epic #230) |
| Prereq ① | installed-version tracking — `SiblingHealth` slice + control-DB cache; `/machine/health` probe with `X-NENE2-API-Key` | #256, #258 |
| Prereq ② | catalog version mirror — ADR 0013 §4 read-model on the catalog API | #259 / #260 |
| Prereq ③ | ADR 0013 accepted | #262 |
| Prereq ④ | upgrade contract — ADR 0018 accepted, then **superseded by ADR 0019** (deployment-driven) | #266, #268, #272 |
| Docs sync | roadmap / current.md kept in step | #254, #264, #270 |

**Cross-repo (NENE2 — separate public framework repo):** NENE2#1414 shipped v1.5.330 (`version` +
`framework_version` on `/machine/health`); NENE2#1416 (`/machine/update`) **withdrawn**;
NENE2#1417/#1418 removed "suite/orchestrator" prose. **Sibling adoption** (for live version data):
nene-invoice#496 / nene-clear#182 / nene-records#586.

---

## 3. Architecture map (new this milestone)

Vertical-slice clean architecture (see prior handover §3). New / changed slices:

- **`src/SiblingHealth/`** — installed-version source.
  - `SiblingHealthClientInterface` + `StreamSiblingHealthClient` — probe `{publicUrl}/machine/health`
    with `X-NENE2-API-Key`; non-throwing (any failure / missing version → null); short-circuits to
    null when no key.
  - `InstalledVersionRepositoryInterface` + `PdoInstalledVersionRepository` — control-DB cache
    (`installed_app_versions`, last-write-wins).
  - `InstalledVersionResolverInterface` + `InstalledVersionResolver` — per app: probe (with the env
    machine key) → persist → fall back to last-known; returns `catalogId → ?version`.
  - `SiblingHealthServiceProvider` (registered in `ApplicationServiceProvider`).
  - Migration `database/migrations/20260625000100_create_installed_app_versions_table.php` +
    `database/schema/installed_app_versions.sql`.
- **`src/SuiteEnv/`** — `SuiteAppMachineKeyReaderInterface` + `EnvSuiteAppMachineKeyReader`
  (`NENE_SUITE_APP_{SNAKE}_MACHINE_KEY`, = the sibling's `NENE2_MACHINE_API_KEY`).
- **`src/AppCatalog/`** — version mirror: `CatalogAppVersions`, `CatalogAppVersionSourceInterface`,
  `OriginCatalogAppVersionSource` (wraps the Origin updates read; defensive empty on failure/disabled);
  `ListCatalogAppsUseCase` now `execute(now)` and enriches; handler serializes
  `installedVersion` / `availableVersion`.
- **`src/Origin/`** — `GetOriginUpdatesUseCaseInterface` extracted (so the catalog consumes the read
  decoupled); `GetOriginUpdatesUseCase` feeds the resolved installed version into `OriginUpdateQuery`.
- **Frontend** — `entities/catalog-app` model/mapper carry the versions; `features/catalog` store card
  shows "Installed x · Latest y"; en/ja i18n; `schema.gen.ts` regenerated.

---

## 4. Invariants & seams a maintainer MUST know

These are the non-obvious rules; breaking them passes a casual read but violates the contract.

1. **The upgrade apply is deployment-driven (ADR 0019).** Suite, as the Tier B Docker Compose owner,
   recreates a sibling's container at the new image; the sibling migrates **on boot** (its own Tier A,
   ADR 0014). **Never** add a sibling runtime self-apply HTTP endpoint, and Suite **never** writes
   inside a sibling tree (ADR 0013 §3/§8). Recreating a container ≠ in-tree mutation.
2. **NENE2 stays Suite-agnostic** (one-directional Suite→NENE2). Litmus test before any NENE2 request:
   *"would NENE2 add this to core even if Suite didn't exist?"* — YES → generic, fine; NO → it belongs
   in Suite / the app. Never name or allude to Suite in NENE2 issues/PRs/code. See
   memory `feedback_nene2_suite_agnostic`.
3. **Installed-version source = the sibling's `/machine/health` `version`** (`X-NENE2-API-Key`), cached
   in the control DB. Not the static `catalog/apps.json` (values are live). The probe runs **only on
   the Origin-enabled path** and is **non-throwing**; absence → `unknown` (defensive). The per-app key
   is `NENE_SUITE_APP_{SNAKE}_MACHINE_KEY` and is **never** written to the install manifest.
4. **The catalog version mirror is a read-model** (`installedVersion` / `availableVersion`, nullable on
   `CatalogApp`), mirrored from the Origin updates read — never originated, never baked into
   `apps.json`. `OriginCatalogAppVersionSource` degrades to an empty map on any failure / Origin
   disabled, so a catalog read is never broken by it.
5. **`SiblingHealthClientInterface` is the seam** — its concrete probes `/machine/health` only (read).
   Anything more for O6 is **deployment orchestration on the Suite side**, not a new sibling endpoint.
6. **Defensive everywhere — no fabricated data.** Every surface keeps an honest `unknown` / placeholder
   until Origin is configured *and* the sibling reports its version.
7. **Production activation is human-gated** — root-key ceremony + `NENE_ORIGIN_URL` /
   `NENE_ORIGIN_TRUST_ANCHOR_PATH`. An unset trust anchor disables the Origin client (fail-closed).
8. **Catalog read couples to Origin only when enabled.** When Origin is unconfigured the catalog read
   stays cheap (no live fetch). Caching the available-version (so the catalog never live-fetches) is a
   deferred follow-up (ADR 0013 §2).

---

## 5. How to run, develop, and verify

Ports, gates, and the governed workflow are unchanged — see the prior handover §5 (composer check /
npm run check; the local `.env` caveat for `ControlDatabaseConfigResolverTest`; ports 8800 / 3389 /
5188; `protect-main`). Milestone-specific notes:

- **Origin is disabled by default** (no `NENE_ORIGIN_URL` / trust anchor) → updates/feeds return
  disabled and the catalog mirror is empty. To exercise verification, the conformance corpus under
  `tests/fixtures/origin-conformance` drives the backend tests.
- **After any `docs/openapi/openapi.yaml` change**, run `npm run codegen` and commit
  `frontend/src/shared/api/schema.gen.ts` (CI freshness gate). The catalog mirror touched the
  `CatalogApp` schema — that path is exercised.
- Backend tests for this work: `tests/SiblingHealth/`, `tests/AppCatalog/`, `tests/Origin/`.

---

## 6. How to extend (next implementer)

- **Resolve ADR 0019 open questions, then accept it** (the design gate). OQ1 (deploy-control
  mechanism: Docker socket vs host-side deploy agent) is a security decision; OQ2 (image provenance
  vs Origin signature); OQ3 (Tier A / release-ZIP coexistence). Follow the ADR-acceptance pattern
  used for ADR 0013 (#262) and ADR 0018 (#268): resolve the OQs in-place, flip status, link PRs.
- **Build the O6 orchestrator (Suite side, deployment-driven):**
  1. Bring sibling services into Suite's compose project (currently `docker-compose.yml` runs only
     `suite` + `db`; siblings are stubs — see CLAUDE.md "unlocking sibling service stubs", fixed ports).
  2. Compute the dependency-ordered upgrade plan over the catalog `requires` DAG; gate on Origin
     min-compatible versions (refuse/halt breaking sets).
  3. Drive `compose pull` + recreate per sibling in order (via the mechanism chosen in OQ1); verify
     each is healthy and at the target version via `/machine/health`; halt the chain on failure (no
     unwind).
  4. Record before/after in the suite audit trail (ADR 0007). Apex "update all" UI. Signed-result
     caching (ADR 0013 §2).
- **A safe first slice** is non-destructive: compute and display the dependency-ordered plan +
  min-version conflicts in the apex shell, with **no apply trigger** — Suite-only, reversible.

---

## 7. Deferred / known gaps

- **ADR 0019 open questions** (OQ1–3) — must be resolved before O6 apply work.
- **No upgrade apply yet** — the orchestrator, deploy-control capability, and apex "update all" UI are
  O6 (#251), not built.
- **Live version data is cross-repo** — until siblings adopt NENE2 v1.5.330+ and inject `appVersion`
  (nene-invoice#496 / nene-clear#182 / nene-records#586), the diff stays `unknown` (defensive). This is
  not a Suite blocker.
- **available-version is live-fetched** when Origin is enabled (catalog read calls the Origin updates
  read). DB caching of the available-version is a deferred optimization (ADR 0013 §2).
- **Siblings are not yet in Suite's compose project** — prerequisite for the deployment-driven O6.

---

## 8. Next steps (ordered)

1. **ADR 0019 → accepted** (resolve OQ1–3; OQ1 is the security-sensitive one).
2. **O6 orchestrator** (deployment-driven): compose integration → dependency-ordered plan + gating →
   recreate + verify + halt → audit → apex "update all" → caching.
3. **(parallel, cross-repo, non-blocking)** sibling adoption for live version data.

---

## 9. References

- ADRs: **0017** (Origin consumption, accepted), **0013** (update aggregation, accepted),
  **0018** (aggregation contract — **superseded**), **0019** (Tier B deployment-driven upgrade,
  **proposed**), **0014** (boot-time migration), **0007** (audit), **0002** (orchestrator).
- Epics: **#230** (Origin consumption, closed), **#251** (upgrade orchestration).
- Milestone: [`../milestones/2026-06-origin-and-upgrade-orchestration.md`](../milestones/2026-06-origin-and-upgrade-orchestration.md).
  Prior handover: [`2026-06-22-multi-tenant-phase-a.md`](./2026-06-22-multi-tenant-phase-a.md).
- Daily report: [`../daily/2026-06-26.md`](../daily/2026-06-26.md).
- Cross-repo: NENE2#1414 (`/machine/health` version, shipped v1.5.330).
- Key code: `src/SiblingHealth/`, `src/AppCatalog/` (version mirror), `src/Origin/GetOriginUpdatesUseCase.php`,
  `src/SuiteEnv/EnvSuiteAppMachineKeyReader.php`.

_Last updated: 2026-06-26. Author: handover for the next maintainer of the upgrade-orchestration work._

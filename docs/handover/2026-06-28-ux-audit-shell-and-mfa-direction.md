# Handover: UX Remediation, Audit Viewer, Sidebar Shell & MFA Direction (2026-06-28)

Single entry point for whoever continues after the 2026-06-27/28 sessions. This handover
is **product-wide state + open challenges + an ordered TODO**, layered on the foundation
captured in the earlier handovers — read those for the multi-tenancy / federation / Origin
internals, this one does not repeat them:

- [`2026-06-22-multi-tenant-phase-a.md`](./2026-06-22-multi-tenant-phase-a.md) — multi-tenant + federation IdP key plane.
- [`2026-06-26-origin-and-o6-prerequisites.md`](./2026-06-26-origin-and-o6-prerequisites.md) — Origin client + O6 prerequisites.
- [`2026-06-26-federation-lifecycle-and-db-topology.md`](./2026-06-26-federation-lifecycle-and-db-topology.md) — ADR 0020 / 0021.
- [`2026-06-26-mode-a-implementation.md`](./2026-06-26-mode-a-implementation.md) — ADR 0022 mode A.

Living status: [`../todo/current.md`](../todo/current.md) (authoritative, kept fresh).
Operating rules: [`../../AGENTS.md`](../../AGENTS.md). `main`'s git log is the shipped record.

---

## 1. Where we are

NeNe Suite is the **installer + orchestrator + IdP** for the NeNe sibling products (it is
not an app monolith — ADR 0002). The platform spine is built and green:

- **Phase 1** (Tier B installer MVP) ✅ · **Multi-tenant Phase A** (A0–A8b) ✅ ·
  **Phase B / B1** (federation IdP key plane + OSS auth hardening) ✅ ·
  **Origin consumption client** (O0–O5b, epic #230) ✅.
- Control DB + provisioning are PostgreSQL-capable (ADR 0016). Upgrade orchestration is
  **deployment-driven** (ADR 0019 supersedes 0018); O6 prerequisites are landed.
- **ADR 0022 mode A** (suite-driven install adopt) shipped end to end. **ADR 0023**
  (post-install DB re-adoption + sibling preflight) accepted.

The last two sessions were **UX-remediation + design-integration + one architecture
decision**, not new platform plumbing. Gate is green throughout.

- Gate state: **PHPUnit 468 / vitest 98**, all green.
- ADRs **0001–0025** on record. Repo is **public**; professional (士業) review is now
  **advisory** — consolidated before a public release, not a per-change gate (ADR 0003 /
  0005 amended, #320).

## 2. What shipped recently (merged to `main`)

This session block, newest first:

| PR | Change |
| --- | --- |
| #346 | Remove the home-greeting **name-color picker** (a ClaudeDesign preview-only control). |
| #344 | **Top nav → left sidebar shell** (full / icon-rail / off-canvas drawer). Closes B3 (#331). Also gates the superadmin-only nav item. |
| #342 | **ADR 0025 — MFA / step-up authentication** (decision; see §4). |
| #340 | **Audit CSV** now carries `before` / `after` / `metadata` + change kind (evidence-grade). |
| #338 | **Audit log list + detail drawer** with a recursive before/after **diff viewer** (ClaudeDesign). |
| #336 | Install wizard **review** echoes the chosen DB target / linkage / app name. |
| #335 | Persona-eval **quick wins (A)** — 5 small UX fixes. |
| #326 | Membership console: role-change / revoke **error surfacing** + last-admin guard. |
| #324 | **In-app help foundation** — glossary, per-screen how-to, tutorials, inline annotations. |
| #320 | Governance: reflect **public repo**; 士業 review binding → advisory. |

Cross-repo, this block:

- **ADR 0025** answered NeNe Clear's MFA question (#341) and filed **NENE2#1427** (generic
  TOTP primitive). Suite reply posted on #341.

## 3. Orientation — where things live

- **Backend** `src/` (PHP 8.4, DDD-ish layering): `Auth/` (login, federation JWKS /
  assertions, rate limiting), `Installer/`, `Database/` (targets, control-DB resolution),
  `Audit/`, `Http/` (route registrars, problem-details). Migrations in `db/migrations`
  (phinx; idempotent, applied on every boot — ADR 0014).
- **Frontend** `frontend/src/` (React 19 / TS / Vite, Feature-Sliced Design):
  - `features/app-shell/` — **the new sidebar shell**: `AppSidebar` (grouped nav, rail/drawer),
    `AppShell` (`[sidebar][main]` + drawer state), `AppHeader` (slim top bar), `use-app-nav`
    (`useAppNavGroups`, superadmin-filtered).
  - `features/audit-viewer/` — list + `AuditDetailDrawer` + `lib/audit-diff` (pure diff
    engine) + `csv`.
  - `features/help/`, `features/dashboard/`, `features/command-palette/`,
    `features/install-wizard/`, org/membership consoles.
  - `shared/i18n/messages/{en,ja}.ts` — en is SSOT, ja must reach parity; 4 other locales
    fall back to en (ADR 0009).
  - `shared/ui/theme/tokens.css` — oklch design tokens incl. the `--side-*` sidebar palette.

## 4. Decisions & seams a maintainer MUST know

- **Suite is the IdP** (ADR 0012). Siblings exchange an SSO assertion for a local session
  (two tokens, two trust domains). Sibling users are JIT-mirrored by `email`; local auth is
  a break-glass fallback. **One-directional dependency: Suite → sibling. NENE2 must stay
  Suite-agnostic** — request generic framework features, never name Suite.
- **MFA / step-up (ADR 0025, new).** Decision: support MFA in **both** modes with one
  mechanism. Generic **TOTP primitive lives in NENE2** (NENE2#1427) — *no new auth repo*.
  Standalone siblings run TOTP in their local login; federated deployments enforce at the
  **Suite IdP** and carry a step-up claim in the assertion (app-layer, not NENE2). MFA is
  **decoupled from the federation roadmap** — Clear can ship standalone MFA (clear#195) now.
- **Upgrades are deployment-driven** (ADR 0019). Suite orchestrates **order / gating /
  relay only**; the sibling applies the migration on boot. Suite never runs a sibling's DDL.
- **Audit is append-only** and carries before/after (ADR 0007) — now fully surfaced in the
  UI + CSV.
- **App "disable" = soft-disable** (data frozen, login redirected to Suite); full delete is
  **undecided**. #333 (B5) touches the reversibility UX.
- **main is protected** (ruleset `protect-main`): PR + 3 CI checks (Backend PHPUnit/PHPStan/CS;
  Frontend type-check+tests; Docs catalog & OpenAPI). No bypass; self-merge allowed once
  green (0 approvals required).

## 5. How to run, develop, verify

- Backend up: `docker compose up db -d` → `docker compose run --rm suite php installer/install.php`
  → `docker compose up -d` → http://localhost:8800. Local operator: `admin@example.com` /
  `devpassword12` (superadmin).
- Frontend dev: `cd frontend && npm ci && npm run dev` → http://localhost:5188.
- **The gate (run before every PR):** `cd frontend && npm run format:fix && npm run check`
  (type-check → lint `--max-warnings 0` → prettier → vitest → build). `npm run codegen` +
  commit `schema.gen.ts` whenever `openapi.yaml` changes.
- Backend tests: `composer test` / PHPStan / CS in the container.
- Doc guards: `bash tools/check-terminology.sh` + `bash tools/check-links.sh`.
- Workflow: branch `type/<issue>-slug` → Conventional Commits w/ `(#issue)` +
  `Co-Authored-By: Claude Opus 4.8` → PR `Closes #N` → wait for CI **CLEAN** →
  `gh pr merge --squash --delete-branch` → sync `main`. Update `current.md` freshness +
  the gate count in the same change.

## 6. Deferred / known gaps (caveats)

- **Local PHPUnit `.env` caveat.** Running the full backend suite locally fails
  `ControlDatabaseConfigResolverTest` (1 test) due to `.env` pollution; move `.env` aside to
  run clean. **CI is unaffected (green).**
- **Audit CSV exports the loaded / filtered set only** (cursor pagination) — not all pages.
  An all-pages or server-side export is a follow-on (see §8).
- **Frontend bundle > 500 kB** (single chunk warning). No code-splitting yet; harmless but
  noted for later.
- **README status badges are unreliable** — judge state from `current.md` + the code, not
  badges.
- **i18n:** only en (SSOT) + ja (parity) are maintained; the other 4 locales are en-fallback
  stubs.

## 7. Open challenges & the next foundation pieces

1. **Federation B2 (cross-repo).** The next platform piece: **sibling-side org resolution +
   authorization-code assertion flow** so a sibling can actually *consume* Suite SSO (today
   the IdP key plane exists, but Clear is standalone-only). This unblocks Clear joining
   federation and is where **Suite IdP MFA enforcement** (ADR 0025) lands. Needs work in a
   sibling repo (Clear) too.
2. **O6 upgrade orchestration (epic #251).** Tier B **deployment-driven** orchestrator
   (compose: dependency-ordered image recreate + min-version gating; sibling migrates on
   boot) + apex "update all" UI. Prereqs landed; depends on sibling `/machine/health`
   version (clear#182) and the preflight contract (clear#183, NENE2#1421).
3. **MFA rollout (ADR 0025).** NENE2#1427 (generic TOTP) → Clear standalone TOTP
   (clear#195) → Suite IdP enforcement (with B2).
4. **App full-delete policy** — currently only soft-disable is decided.

## 8. Next steps (ordered TODO)

**Near-term, in this repo — finish the persona-eval B group (epic #327):**

1. **#332 (B4) — a11y:** command-palette / Modal focus trap + screen-reader announcements.
   *Do first* — accessibility correctness, self-contained.
2. **#330 (B2) — help i18n:** English locale shows JP-only help body with no "not translated"
   signal; add a fallback signal (ties into ADR 0009 / ADR 0024).
3. **#333 (B5) — org reversibility:** make disable reversibility explicit + a re-enable path +
   impact preview (touches the soft-disable seam in §4).
4. **#334 (B6) — locale toggle:** header LocaleToggle traps non-en/ja; mount LocaleSwitcher /
   clamp correctly.
5. Sweep the remaining **epic #327** medium/low backlog items.

**Platform (larger, cross-repo) — pick up when the UX sweep is done:**

6. **Federation B2** (§7.1) — design the sibling SSO-consumption flow; coordinate with Clear.
7. **O6 orchestrator + "update all" UI** (epic #251).
8. **Audit CSV all-pages / server-side export** (closes the §6 gap).

**Cross-repo follow-ons to track (not this repo's PRs):**

- **NENE2#1427** generic TOTP primitive · **NENE2#1421** preflight signing (3/3).
- **nene-clear#195** standalone MFA · **clear#182** `/machine/health` version · **clear#183**
  candidate-DB preflight adoption.

## 9. References

- ADRs (driving): [0012](../adr/0012-federation-participation-contract.md) (federation),
  [0013](../adr/0013-update-aggregation-and-upgrade-orchestration.md) /
  [0019](../adr/0019-tier-b-deployment-driven-upgrade.md) (upgrades),
  [0020](../adr/0020-federated-user-lifecycle.md) (user lifecycle),
  [0022](../adr/0022-app-onboarding-modes.md) (onboarding),
  [0023](../adr/0023-post-install-database-re-adoption.md) (DB re-adoption / preflight),
  [0024](../adr/0024-in-app-help-content-model.md) (help),
  [0025](../adr/0025-mfa-step-up-authentication.md) (MFA / step-up).
- Living status: [`../todo/current.md`](../todo/current.md). Roadmap: [`../roadmap.md`](../roadmap.md).
- Issues: persona epic **#327** (B2 #330 · B4 #332 · B5 #333 · B6 #334); upgrade epic **#251**;
  MFA question **#341**.

# Handover: UX Remediation Complete, ADR 0019 Accepted, O6 Ready to Build (2026-07-03)

Single entry point for whoever continues after the 2026-07-02/03 sessions. This handover is
**product-wide state + open challenges + an ordered TODO**, layered on the foundation captured
in the earlier handovers — read those for the multi-tenancy / federation / Origin internals,
this one does not repeat them:

- [`2026-06-28-ux-audit-shell-and-mfa-direction.md`](./2026-06-28-ux-audit-shell-and-mfa-direction.md) — shell revamp, audit viewer, MFA direction (ADR 0025).
- [`2026-06-26-origin-and-o6-prerequisites.md`](./2026-06-26-origin-and-o6-prerequisites.md) — Origin client + O6 prerequisites.
- [`2026-06-26-federation-lifecycle-and-db-topology.md`](./2026-06-26-federation-lifecycle-and-db-topology.md) — ADR 0020 / 0021.
- [`2026-06-22-multi-tenant-phase-a.md`](./2026-06-22-multi-tenant-phase-a.md) — multi-tenant + federation IdP key plane.

Living status: [`../todo/current.md`](../todo/current.md) (authoritative, kept fresh).
Operating rules: [`../../AGENTS.md`](../../AGENTS.md). `main`'s git log is the shipped record.

---

## 1. Where we are

The platform spine is complete and green; the polish debt is paid; the **next major arc — the
O6 upgrade orchestrator (epic #251) — is unblocked and sliced into issues** (#361–#364).

- **Phase 1** (Tier B installer MVP) ✅ · **Multi-tenant Phase A** ✅ · **B1** (federation IdP
  key plane) ✅ · **Origin consumption client** (epic #230) ✅ · **ADR 0022 mode A** ✅ ·
  **ADR 0023** accepted.
- **10-persona UX-remediation epic #327 is done**: the A quick wins, all six B findings
  (#329–#334), and the medium backlog are resolved — the only explicit defer is server-side
  org-list pagination (needs a list-API extension; recorded in the epic).
- **ADR 0019 (deployment-driven upgrades) is accepted** (2026-07-02 amendment, PR #359) with
  all its open questions resolved or explicitly deferred — see §4.
- **ADR 0025 (MFA / step-up)**: the NENE2 side **shipped** — the generic TOTP primitive +
  recovery codes (NENE2#1427) landed in **NENE2 v1.5.333** (verified against the tag). The
  Suite-side IdP enforce + step-up claim remains future work. The cross-repo question issue
  (#341) is answered and closed.
- **nene-clear adopted both machine-surface contracts** (clear#182/#183 → clear PR#240/#241,
  2026-07-03): its `/machine/health` reports the installed version and its candidate-database
  preflight is live. nene-invoice and nene-records have not adopted yet, so their installed
  versions stay `unknown` (defensive, no fabrication).
- Gate state: PHPUnit **476** / vitest **134** / PHPStan / CS / terminology / links / OpenAPI —
  all green. CI + staging deploy to the staging host remain live.

## 2. What shipped recently (merged to `main`, since the 2026-06-28 handover)

| Issue / PR | What |
| --- | --- |
| #332 / PR #349 | **a11y focus management**: shared `useFocusTrap` (initial focus, Tab/Shift+Tab cycle, trigger restore) on Modal / Drawer / CommandPalette; palette upgraded to the WAI-ARIA APG **combobox** pattern (`aria-activedescendant`, managed-focus options, `role="status"` empty state); AccountMenu → menu-button pattern; NotificationsMenu trapped + restored; install stepper `aria-current="step"` + visually-hidden live region; `<ol>` connector spans → pseudo-elements (valid list semantics). |
| #330 / PR #350 | **Help i18n signal**: every non-ja locale sees an explicit "help articles are Japanese-only, English in preparation" notice on all `/help` routes (ADR 0024's Japanese-first body unchanged); all help UI chrome moved into the message catalogs (`suite.help.*` +32 keys, en/ja parity; group/category headings are now `MessageKey` maps in the content layer). |
| #333 / PR #351 | **Org disable reversibility**: new `POST /api/v1/organizations/{id}/enable` (superadmin-only, idempotent, `organization.enabled` audited with before/after — a full mirror of disable); the disable confirm spells out the impact (reversible freeze, not deletion; members locked out; data kept; re-enable anytime; audited); disabled rows have a working **Re-enable** flow. OpenAPI 31 → 32 operations. |
| #334 / PR #352 | **Locale-toggle clamp**: header toggle clamps into the maintained en+ja pair (stub locales → **en**, never implicitly to ja); the six-locale `LocaleSwitcher` is mounted at Settings → General → Language (the one picker where every locale is reachable/escapable); clamp spec documented in `docs/development/i18n.md`; `resolveLocale` aliases zh / zh-CN / zh-SG → zh-Hans (zh-TW / zh-Hant keep the en fallback). |
| PR #353 | **Freshness**: NENE2#1427 verified shipped in NENE2 v1.5.333; sibling adoption status corrected across README / roadmap / current. |
| PR #354–#357 | **#327 medium sweep** (one PR per item): memberships console **target-organization banner** (name + slug + Disabled badge); **zh-Hans font stack** leads with simplified-Chinese fonts (JP-glyph-variant fix; Noto Sans SC added to the font link); org console **name/slug filter** + client-side **slug-rule error** mirroring the backend pattern; install wizard **Back links** (wires `goToStep`; selection prefilled on return) + **Enter submission** (`<form>`-wrapped steps). |
| PR #358 | Daily report 2026-07-02 + status freshness. |
| PR #359 | **ADR 0019 amendment accepted**: OQ1/OQ2 resolved, OQ3 deferred (§4). Deploy capability wording fixed to *opt-in capability flag (default off)* during review. |
| PR #360 | Freshness: nene-clear probe/preflight adoption reflected. |
| cross-repo | **nene-clear PR#240/#241** (clear#182/#183): `/machine/health` version + `NENE2_MACHINE_API_KEY` gating; candidate-database preflight (inspector + identity marker migration + env allowlist). Clear's backend suite: 352 tests green. |
| #341 | Closed — MFA placement answered (ADR 0025); NENE2 primitive shipped (v1.5.333). |

## 3. Orientation — where the new pieces live

- `frontend/src/shared/ui/components/use-focus-trap.ts` — shared overlay focus management;
  applied in `Modal` / `Drawer` / `CommandPalette` / `NotificationsMenu`.
- `frontend/src/shared/ui/locale/LocaleSwitcher.tsx` — now styleable via optional class props;
  mounted in `features/settings/ui/SettingsView.tsx`.
- `frontend/src/features/help/content/{guides,glossary}.ts` — group/category labels are
  `MessageKey` maps (`GUIDE_GROUP_LABEL_KEY` / `GLOSSARY_CATEGORY_LABEL_KEY`); the body prose
  stays the ADR 0024 exception.
- `src/Tenancy/EnableOrganization*.php` — the enable use case / handler (mirror of disable).
- `docs/adr/0019-tier-b-deployment-driven-upgrade.md` — **accepted**; the "Resolved questions
  (2026-07-02 amendment)" section is the O6 implementation contract.
- O6 implementation slices: issues **#361 → #362 → #363 → #364** (epic #251 has the status
  comment tying them together).
- nene-clear adoption: clear `src/Database/{ApplicationDatabaseIdentity,MigrationVersions,CandidateProfiles}.php`,
  migration `20260703000000_stamp_application_identity`, `tests/Http/{MachineHealthTest,DatabasePreflightTest}.php`.

## 4. Decisions & seams a maintainer MUST know

- **ADR 0019 resolutions (the O6 contract).**
  - **OQ1**: deployment control is a **host-side deploy agent** — the Suite container never
    gets the Docker socket. The agent acts only on an explicit allow-list of compose services,
    only `pull` + recreate; every request/result is audited (ADR 0007). The capability is an
    **opt-in capability flag, default off, available to every edition** (OSS self-host
    included — it is *not* hosted-only); without the agent the feature degrades to "updates
    visible, apply manual". The request transport (file/queue handshake etc.) is decided in
    slice #361, not in the ADR.
  - **OQ2**: image provenance is **staged** — Stage 1 pins an immutable **image digest** in the
    catalog and independently verifies the post-recreate version via the sibling's
    `GET /machine/health`; Stage 2 (Origin-signed digests) waits for Origin's own build-out and
    **must not block O6**.
  - **OQ3**: Tier A (release-ZIP web installer) coexistence is **explicitly deferred** until the
    product-family installer toolkit lands.
- **Org disable/enable**: disable is a reversible freeze (never deletion — ADR 0012 §5/§11);
  enable is its idempotent mirror; both audited with before/after. The UI copy states this;
  keep it truthful.
- **Locale pickers**: the header toggle serves only the maintained en+ja pair (clamp spec in
  `docs/development/i18n.md`); the Settings `LocaleSwitcher` is the only full picker. Do not
  re-introduce a path that strands a stub-locale user.
- **Help i18n**: body prose stays Japanese-first (ADR 0024 exception); *everything else* goes
  through the catalogs. The non-ja notice must stay until an English body ships.
- **nene-clear machine-surface seams** (matter when configuring deployments or repeating the
  adoption in other siblings):
  - Key pairing: clear `NENE2_MACHINE_API_KEY` ↔ Suite `NENE_SUITE_APP_NENE_CLEAR_MACHINE_KEY`.
    Until paired in a deployment, Suite keeps reporting `unknown` for clear (by design).
  - Clear's Phinx ledger table is **`phinxlog`** — the NENE2 inspector default (`phinx_log`)
    will silently never recognize the schema; the ledger name must be wired explicitly.
  - The identity-marker migration writes the marker table with plain Phinx ops because
    `ApplicationIdentityMarker` opens its own transaction, which conflicts with the transaction
    Phinx holds around a migration (hit for real, then fixed).
  - Candidate databases come only from the `NENE_CLEAR_DB_CANDIDATE_*` env allowlist —
    connection details never come from the request body (SSRF prevention).
- **One-directional dependency**: Suite → sibling. NENE2 stays Suite-agnostic — anything Suite
  needs from the framework is requested as a generic capability, never Suite-named.
- **main is protected** (ruleset `protect-main`): PR + 3 CI checks, no bypass; squash-merge
  after green.

## 5. How to run, develop, verify

- **Backend**: `docker compose --env-file .env.suite …` per `CLAUDE.md` (apex on :8800, MySQL
  :3389). Full local suite: `vendor/bin/phinx … / vendor/bin/phpunit --testsuite NeNeSuite`
  — note a populated local `.env` makes `ControlDatabaseConfigResolverTest` fail (env leakage);
  stash `.env` for a clean run. CI is unaffected. `composer analyse` / `composer cs` for
  PHPStan L8 / CS-Fixer.
- **Frontend**: `cd frontend && npm run format:fix && npm run check` — the check bundle
  (type-check → eslint `--max-warnings 0` → prettier → vitest → build) is the only accepted
  gate; running pieces individually has missed failures before. After touching
  `docs/openapi/openapi.yaml`, run `npm run codegen` and commit `schema.gen.ts` in the same PR
  (CI-gated).
- **Docs guards**: `bash tools/check-terminology.sh` + `bash tools/check-links.sh`.
- **Gate baselines** (update `current.md` in the same PR when they move): PHPUnit **476** /
  vitest **134**.

## 6. Deferred / known gaps (caveats)

- **Server-side org-list pagination** — the one explicit defer from epic #327; needs a
  list-API extension (cursor/limit) when tenant counts warrant it.
- **Origin-signed image provenance** (ADR 0019 OQ2 Stage 2) — waits on Origin; O6 ships with
  the catalog digest pin + post-recreate verification.
- **Tier A coexistence** (ADR 0019 OQ3) — deferred until the family installer toolkit lands.
- **Sibling adoption gaps**: nene-invoice (#496 machine route unwired, #497 preflight) and
  nene-records (#586 — the app currently has **no version identity source at all**, decide the
  mechanism before wiring; #648 preflight). Until adopted, their versions stay `unknown`.
- **ADR 0020** (federated user lifecycle) — accepted, not yet implemented (B2 follow-on).
- **MFA Suite side** (ADR 0025) — IdP enforce + step-up claim in the assertion; the NENE2
  primitive is shipped and standalone siblings can proceed independently.

## 7. Open challenges (decisions pending — do not pre-empt)

- **B2 pilot selection**: which sibling first for org resolution + authorization-code assertion
  flow. Engineering facts: nene-invoice already has `organizations.external_id` +
  `findByExternalId` and four org-resolution modes in production; nene-clear deliberately
  decoupled federation (slug+JWT only, zero federation code).
- **JWKS verification placement**: a generic NENE2 capability vs per-sibling implementation
  (multiple products will need it).
- **nene-records version identity**: no version source exists; the mechanism (family-common
  convention vs per-app) should be decided once, not improvised per repo.

## 8. Next steps (ordered TODO)

1. **#361 — O6 S2-1a: deploy-control seam + audit.** Capability flag (default off), deploy
   request persistence, agent handshake contract, allow-list, before/after audit. OpenAPI →
   codegen → implement → test.
2. **#362 — O6 S2-1b: dependency-ordered update plan + min-version gating** (read-only plan
   op; digest-pinned targets; explicit handling of `unknown` versions).
3. **#363 — O6 S2-1c: halt-don't-unwind execution** (drive the plan through the seam; verify
   each step via `/machine/health`; resume from the failed step).
4. **#364 — O6 S2-1d: apex "update all" UI** (plan display, conflicts, confirm-with-impact
   copy per the #333 pattern, SR live region per the #332 pattern; honest degrade when the
   capability is off).
5. **Ops**: pair the clear machine key in the deployments so clear's installed version goes
   live (`unknown` → real diff).
6. **nene-invoice / nene-records adoption** (#496/#497, #586/#648) — after their §7
   preconditions are settled.
7. **B2** — sibling-side org resolution + assertion flow, after the pilot decision; then
   ADR 0020 lifecycle and the ADR 0025 Suite-side enforce.

## 9. References

- ADRs: 0019 (accepted, amended 2026-07-02) · 0020 · 0021 · 0022 · 0023 · 0024 · 0025.
- Epic #251 (+ status comment 2026-07-03) · slices #361–#364 · epic #327 (complete, one defer).
- Daily reports: [`../daily/2026-07-02.md`](../daily/2026-07-02.md) ·
  [`../daily/2026-07-03.md`](../daily/2026-07-03.md).
- Cross-repo: nene-clear PR#240/#241 (clear#182/#183) · NENE2 v1.5.333 (NENE2#1427).

Last updated: 2026-07-03

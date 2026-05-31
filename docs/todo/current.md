# Current TODO

**Phase 0 — Governance and product design**

## Done

- [x] Issue #1: Governance bootstrap — PR #2
- [x] Issue #3: Installer disclaimer — PR #4
- [x] Issue #5–#6: Review gate + env contract — PR #7
- [x] Issue #8: Orchestration compliance — PR #9
- [x] Issue #10: Terminology registry — PR #11
- [x] Issue #12: Audit trail (before/after) — merged
- [x] Issue #14: NENE2 coding standards — merged
- [x] Issue #16: i18n message catalogs — merged (PR #17)
- [x] Issue #18: Phase 1 OpenAPI contract — merged (PR #19)
- [x] Issue #20: Catalog validation script — merged (PR #21)
- [x] Issue #22: CI workflow (terminology + catalog + OpenAPI lint + frontend) — merged (PR #23)
- [x] Issue #24: Untrack frontend/node_modules + .gitignore fix — merged (PR #25)
- [x] Issue #26: Backend scaffold + first slice (AppCatalog read) — merged (PR #27)
- [x] Issue #28: Backend slice 2 — control DB + SuiteAudit recorder + InstallSession start/get — merged (PR #29)
- [x] Issue #30: Backend slice 3 — app-selection with dependency resolution — merged (PR #31)
- [x] Issue #32: Problem Details base for framework errors → `nene-suite.dev` (NENE2 v1.5.328 / NENE2#1355) — merged (PR #33)
- [x] Issue #34: Backend slice 4 — install-session disclaimer-acceptance + fail — merged (PR #35)
- [x] Issue #36: Backend slice 5 — completeInstallSession + InstallManifest (ADR 0010) — merged (PR #37)
- [x] Issue #38: Backend slice 6 — OpenAPI contract validation (`composer openapi`) + route↔spec test — merged (PR #39)
- [x] Issue #40: OpenAPI contract — apex auth session — merged (PR #41)
- [x] Issue #42: Backend slice 7 — apex Auth domain (operator + JWT session) — merged (PR #43)
- [x] Issue #44: Backend slice 8 — suite-audit-events read (R-08, paginated, authenticated) — merged (PR #45)
- [x] Issue #46: Backend slice 9 — SuiteEnv URL reader + installed-apps launcher (R-06) — merged (PR #47)
- [x] Issue #48: Backend slice 10 — populate install manifest apps[] (SuiteEnv URLs + DatabaseProvision names) — merged (PR #49)
- [x] Issue #50: Frontend slice 1 — strict toolchain + shared/api + auth login vertical — merged (PR #51)
- [x] Issue #52: Frontend slice 2 — installed-app entity + app-launcher feature (apex home) — merged (PR #53)
- [x] Issue #54: Frontend slice 3 — install-session + catalog-app entities + install wizard — merged (PR #55)
- [x] Issue #56: Frontend slice 4 — suite-audit-event entity + audit viewer (admin) — merged (PR #57)
- [x] Issue #58: Frontend slice 5 — shared/ui primitives (AsyncStates/PageHeader) + locale switcher — PR pending

### Frontend: all Phase 1 API surfaces have UI ✅ — login · launcher (installed-apps) · install wizard · audit viewer, with shared/ui primitives + locale switching. Strict layering enforced by ESLint boundaries; every feature has a Vitest+MSW test.

## Phase 1 OpenAPI: all 13 operations implemented ✅

health · catalog · install-session (start/get/app-selection/disclaimer/complete/fail) · auth session (create/get/delete) · suite-audit-events · installed-apps. Every mutation has before/after audit; all Problem `type` use `nene-suite.dev`; `composer openapi` + route↔spec test guard the contract.

## Next (Phase 1 → Phase 2)

- [ ] **税理士 / 公認会計士 sign-off** — orchestration-compliance §2–§4 (template: professional-sign-off-record.md)
- [ ] **弁護士 sign-off** — disclaimer + installer copy
- [ ] **Provisioning (write side)**: `SuiteEnv` env **generation** + `DatabaseProvision` actual DB creation (Tier B); `app_versions` pinning; audit `env_config.written` / `database.provisioned` (manifest `apps[]` now populated — #48)
- [ ] **Tier B installer** (Docker Compose MVP, Invoice + Clear) — calls the same use cases; **blocked on professional sign-off** per Blockers
- [ ] Operator provisioning — first apex operator created by the installer / org-admin flow
- [x] **Frontend slices 1–5**: login · launcher · install wizard · audit viewer · shared/ui + locale switcher — PR #51–#59
- [ ] Shared apex auth middleware (≥2 endpoints now authenticate via `BearerTokenAuthenticator`)
- [ ] `IntegrationWiring`; `NENE_SUITE_CONTROL_DATABASE_URL` resolution + installer ADR (deferred)
- [ ] Backend: `SuiteEnv` (NENE_SUITE_* generation) + `DatabaseProvision` → populate manifest `apps[]` / `app_versions`; `IntegrationWiring`
- [ ] `NENE_SUITE_CONTROL_DATABASE_URL` resolution + installer ADR (deferred from #28)
- [ ] Docker Compose installer MVP (Invoice + Clear)
- [x] Docs relative-link check tool + add to CI (deferred from #22) — PR #63
- [x] `composer openapi` PHP validator + add to CI backend job (follow-up to #18/#26) — PR #62
- [ ] Frontend codegen + `entities/` from OpenAPI (follow-up to #18)

## Blockers

- External installer MVP blocked until professional sign-off records merged.
- Sibling apps need `NENE_SUITE_MODE` env readers (cross-repo Issues).
- Tier A suite wizard depends on sibling release ZIP installers (Invoice Phase 3).

## Handoff

Private meta repo. Compliance model mirrors nene-invoice `accounting-compliance.md`.
Binding trio: scope-contract + orchestration-compliance + disclaimer.

Last updated: 2026-05-31

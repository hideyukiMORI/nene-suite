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
- [x] Issue #58: Frontend slice 5 — shared/ui primitives (AsyncStates/PageHeader) + locale switcher — merged (PR #59)
- [x] Issue #60: `composer openapi` を CI backend job に追加 — PR #62
- [x] Issue #61: Docs 相対リンクチェックツール + CI 追加 — PR #63
- [x] Issue #65: SuiteEnv 生成 + DatabaseProvision write side use case — PR #66
- [x] Issue #67: CreateOperator use case (初回 apex operator 作成) — PR #68
- [x] Issue #69: WriteEnvConfig / ProvisionAppDatabases use case をサービスプロバイダーに配線 — PR #70
- [x] Issue #71: frontend api-types → schema.gen.ts generated types に置き換え — PR #72
- [x] Issue #73: ADR 0011 — NENE_SUITE_CONTROL_DATABASE_URL 解決方針 — PR #74

### Frontend: all Phase 1 API surfaces have UI ✅ — login · launcher (installed-apps) · install wizard · audit viewer, with shared/ui primitives + locale switching. Strict layering enforced by ESLint boundaries; every feature has a Vitest+MSW test.

## Phase 1 OpenAPI: all 13 operations implemented ✅

health · catalog · install-session (start/get/app-selection/disclaimer/complete/fail) · auth session (create/get/delete) · suite-audit-events · installed-apps. Every mutation has before/after audit; all Problem `type` use `nene-suite.dev`; `composer openapi` + route↔spec test guard the contract.

## Next (Phase 1 → Phase 2)

- [x] **税理士 / 公認会計士 sign-off** — orchestration-compliance §2–§5, ADR 0005 — 辻村総合会計事務所 / 2026-05-31 (Issue #75, PR #76)
- [x] **弁護士 sign-off** — disclaimer.md + installer-disclaimer-copy.md — 西村法律事務所 / 2026-05-31 (Issue #77, PR #78)
- [ ] **Tier B installer** (Docker Compose MVP, Invoice + Clear) — 🟢 士業サインオフ完了・外部リリースゲート解除済み
- [ ] `ControlDatabaseConfigResolver` 実装 — ADR 0011 follow-up; `phinx.php` と `RuntimeServiceProvider` を更新
- [ ] Operator provisioning HTTP endpoint — `CreateOperatorUseCase` はあるが Phase 1 では HTTP 未公開
- [ ] Shared apex auth middleware — `BearerTokenAuthenticator` を 4 handler が直接呼ぶパターンのまま (Phase 2 で middleware 化)
- [ ] `IntegrationWiring` — Phase 2 スコープ
- [ ] `app_versions` pinning in manifest — Phase 2 スコープ
- [ ] Docker Compose installer MVP (Invoice + Clear)

## Blockers

- ~~External installer MVP blocked until professional sign-off records merged.~~ **🟢 Resolved 2026-05-31 — both sign-offs on record.**
- Sibling apps need `NENE_SUITE_MODE` env readers (cross-repo Issues).
- Tier A suite wizard depends on sibling release ZIP installers (Invoice Phase 3).

## Handoff

Private meta repo. Compliance model mirrors nene-invoice `accounting-compliance.md`.
Binding trio: scope-contract + orchestration-compliance + disclaimer.

Last updated: 2026-05-31

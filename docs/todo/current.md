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
- [x] Issue #30: Backend slice 3 — app-selection with dependency resolution — PR pending

## Next (Phase 0 → Phase 1)

- [ ] **税理士 / 公認会計士 sign-off** — orchestration-compliance §2–§4 (template: professional-sign-off-record.md)
- [ ] **弁護士 sign-off** — disclaimer + installer copy
- [ ] **Problem Details base**: framework-level errors (ValidationException/no-route/500) use NENE2 default `nene2.dev` base; terminology §13 requires `nene-suite.dev`. Fix via suite response middleware or NENE2 configurability (cross-repo). Domain handlers already use `nene-suite.dev`.
- [ ] Backend slice 4: remaining InstallSession lifecycle — `disclaimer-acceptance` / `fail` (reuse `update()`), then `complete` (+ manifest), each with audit
- [ ] Backend: `SuiteEnv` / `InstallManifest` / `IntegrationWiring` + `installed-apps` + `suite-audit-events` read endpoints
- [ ] `NENE_SUITE_CONTROL_DATABASE_URL` resolution + installer ADR (deferred from #28)
- [ ] Docker Compose installer MVP (Invoice + Clear)
- [ ] Docs relative-link check tool + add to CI (deferred from #22)
- [ ] `composer openapi` PHP validator + add to CI backend job (follow-up to #18/#26)
- [ ] Frontend codegen + `entities/` from OpenAPI (follow-up to #18)

## Blockers

- External installer MVP blocked until professional sign-off records merged.
- Sibling apps need `NENE_SUITE_MODE` env readers (cross-repo Issues).
- Tier A suite wizard depends on sibling release ZIP installers (Invoice Phase 3).

## Handoff

Private meta repo. Compliance model mirrors nene-invoice `accounting-compliance.md`.
Binding trio: scope-contract + orchestration-compliance + disclaimer.

Last updated: 2026-05-30

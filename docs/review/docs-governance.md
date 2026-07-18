# Self-Review — Documentation / Governance

Use before merging docs-only or governance PRs.

## Scope

- [ ] Change matches `docs/explanation/scope-contract.md` (orchestrator only).
- [ ] No product domain logic described as in-scope for this repo.
- [ ] No business, legal, tax, or compliance **guarantee** language (see `docs/explanation/disclaimer.md`).
- [ ] SSOT / DB / federation rules in `docs/explanation/orchestration-compliance.md` respected if install or catalog touched.
- [ ] Identifiers match `docs/explanation/terminology.md` (ADR 0006); [`docs/review/terminology.md`](terminology.md) when terms touched.
- [ ] Standalone + suite dual mode still documented if touched.

## Workflow

- [ ] GitHub Issue exists and PR body includes `Closes #number`.
- [ ] Branch name follows `type/issue-number-summary`.
- [ ] Commit messages follow `docs/development/commit-conventions.md`.

## Catalog / integration

- [ ] `catalog/apps.json` dependency order is acyclic if catalog changed.
- [ ] Sibling repo names and boundaries match `docs/integrations/sibling-products.md`.

## Memory files

- [ ] private `nene-origin/internal-docs/suite/todo/current.md` updated when phase or Issue state changes.
- [ ] `docs/roadmap.md` or milestone updated when direction changes.

## Language

- [ ] Repository documentation remains English.

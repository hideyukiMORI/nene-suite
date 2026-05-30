# Self-Review — Documentation / Governance

Use before merging docs-only or governance PRs.

## Scope

- [ ] Change matches `docs/explanation/scope-contract.md` (orchestrator only).
- [ ] No product domain logic described as in-scope for this repo.
- [ ] Standalone + suite dual mode still documented if touched.

## Workflow

- [ ] GitHub Issue exists and PR body includes `Closes #number`.
- [ ] Branch name follows `type/issue-number-summary`.
- [ ] Commit messages follow `docs/development/commit-conventions.md`.

## Catalog / integration

- [ ] `catalog/apps.json` dependency order is acyclic if catalog changed.
- [ ] Sibling repo names and boundaries match `docs/integrations/sibling-products.md`.

## Memory files

- [ ] `docs/todo/current.md` updated when phase or Issue state changes.
- [ ] `docs/roadmap.md` or milestone updated when direction changes.

## Language

- [ ] Repository documentation remains English.

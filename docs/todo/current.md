# Current TODO

**Phase 0 — Governance and product design**

## Done

- [x] Issue #1: Governance bootstrap — PR #1

## Next (Phase 0 → Phase 1)

- [ ] Issue #2: Suite environment contract ADR (`NENE_SUITE_*`)
- [ ] Issue #3: Catalog validation script
- [ ] Issue #4: Docker Compose installer MVP (Invoice + Clear)
- [ ] Issue #5: CI workflow (catalog schema + docs link check)

## Blockers

- Sibling apps need `NENE_SUITE_MODE` env readers (cross-repo Issues).
- Tier A suite wizard depends on sibling release ZIP installers (Invoice Phase 3).

## Handoff

Private meta repo. Inherit workflow from NENE2 / NeNe Records.
Do not vendor sibling product source.

Last updated: 2026-05-29

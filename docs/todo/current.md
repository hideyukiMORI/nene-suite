# Current TODO

**Phase 0 — Governance and product design**

## Done

- [x] Issue #1: Governance bootstrap — PR #2
- [x] Issue #3: Installer disclaimer — PR #4
- [x] Issue #5: Professional review gate — PR pending
- [x] Issue #6: Suite environment contract (`NENE_SUITE_*`) — PR pending

## Next (Phase 0 → Phase 1)

- [ ] Issue #7: Catalog validation script
- [ ] Issue #8: Docker Compose installer MVP (Invoice + Clear)
- [ ] Issue #9: CI workflow (catalog schema + docs link check)

## Blockers

- Sibling apps need `NENE_SUITE_MODE` env readers (cross-repo Issues).
- Tier A suite wizard depends on sibling release ZIP installers (Invoice Phase 3).
- Public release blocked until ADR 0003 professional review gate is satisfied.

## Handoff

Private meta repo. Inherit workflow from NENE2 / NeNe Records.
Do not vendor sibling product source.
Env contract: ADR 0004 + `.env.suite.example`.

Last updated: 2026-05-29

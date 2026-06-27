# ADR 0001: Inherit NENE2 Governance

## Status

accepted

## Context

NeNe Suite is a public meta repository that orchestrates multiple NENE2-based
sibling products. Operators and AI agents already follow Issue-driven workflow,
Conventional Commits, ADRs, and PR merge policy across the portfolio.

Reinventing governance in this repository would create drift and confuse agents
moving between product repos and the suite repo.

## Decision

NeNe Suite **inherits** portfolio governance from NENE2 and NeNe Records:

- GitHub Issue before substantive change
- Branch `type/issue-number-summary`; no direct commits to `main`
- Conventional Commits: English `type`/`scope`, Japanese description/body, `(#issue)` in subject
- ADRs for architectural decisions (`docs/development/adr.md`)
- Local project memory: `docs/roadmap.md`, `docs/milestones/`, `docs/todo/current.md`
- Self-review checklists before PR when applicable

Suite-specific rules are additive ADRs (for example orchestrator boundary, catalog schema).

## Consequences

**Benefits.**

- One workflow vocabulary across the portfolio.
- AI agents can reuse the same operating rules.

**Costs.**

- Suite must maintain its own ADRs when installer behavior diverges from product apps.
- Commit language policy matches Records (Japanese descriptions), not publication-strategy (English).

## Related

- Issue: `#1`
- PR: `#1`
- Supersedes: none
- Superseded by: none

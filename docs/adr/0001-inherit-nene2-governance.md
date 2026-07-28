# ADR 0001: Inherit NENE2 Governance

## Status

accepted

> **Note (2026-07-29, [#407](https://github.com/hideyukiMORI/nene-suite/issues/407)).** The
> decision text below still names `docs/todo/current.md` as local project memory. That path no
> longer exists in this public repository: the operational logs (todo / daily / handover) moved to
> the private mirror `nene-origin/internal-docs/suite/` in P3 (#405 / #406). The **decision** —
> inherit portfolio governance, keep a living local memory — is unchanged; only the location moved.
> The decision text is left as recorded, because an ADR is a record of what was decided when, not a
> live pointer. Live pointers are in [`AGENTS.md`](../../AGENTS.md) and
> [`README.md`](../../README.md).

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

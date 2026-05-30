# ADR 0002: Orchestrator, Not Application Monolith

## Status

accepted

## Context

NeNe Suite installs multiple products (Records, Invoice, Clear, Corpus, Vault, Profile, …)
on one host with shared login and coordinated environment. Two architectures were considered:

1. **Monolith** — merge products into one codebase and one database.
2. **Orchestrator** — keep separate repos, databases, and HTTP APIs; suite installs and wires them.

The portfolio already standardizes on separate repositories, separate databases, and HTTP-only
integration (sibling ADR 0002 in each product). A monolith would break standalone ZIP installs,
increase compliance drift, and contradict the back-office map (Invoice SSOT, Clear reconciliation, etc.).

## Decision

NeNe Suite is an **installer and orchestrator only**:

- It **does not** contain product domain logic.
- It **does not** vendor sibling source trees into this repository long-term.
- It installs **release artifacts** (ZIP or git tag) per selected catalog entry.
- It writes **suite environment** (`NENE_SUITE_MODE=1`, org UUID, JWT issuer, sibling base URLs).
- Each app keeps its **own database**; cross-app links use **HTTP APIs** only.
- **Standalone install** remains supported: direct git clone / product ZIP with `NENE_SUITE_MODE=0` or unset.

## Consequences

**Benefits.**

- Aligns with existing sibling product boundaries.
- Operators can add or omit apps without rewriting domain code.
- Tier A product installers remain reusable — suite calls the same migrate/bootstrap paths with prefilled config.

**Costs.**

- Suite must maintain a catalog, dependency order, and env contract.
- Federation (org UUID, SSO) requires coordinated changes in sibling apps — tracked via Issues in each repo.
- Path-prefix deployment (`/nene-invoice/`) adds frontend base-path work in each product.

## Related

- Issue: `#1`
- PR: `#1`
- Catalog: `catalog/apps.json`
- Supersedes: none
- Superseded by: none

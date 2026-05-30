# Roadmap

NeNe Suite — **installer and orchestrator** for NeNe sibling products.
See [ADR 0002](./adr/0002-orchestrator-not-application-monolith.md).

## North Star

Operators install only the NeNe apps they need, once, with shared org context and apex navigation —
while each product remains independently installable.

## Phase 0: Governance and Foundation

- Governance docs, ADR 0001–0005, catalog stub ✅
- Orchestration compliance (士業 review pattern) ✅ Issue #8
- 税理士 / 公認会計士 + 弁護士 sign-off on binding docs 🔲

## Phase 1: Tier B Installer MVP

- Docker Compose orchestrator
- Select Invoice + Clear; provision DBs + env + service token
- Apex shell (login + app links)
- Document add-app workflow

## Phase 2: Federation Contract

- Shared JWT issuer / org UUID propagation
- Sibling app changes tracked via cross-repo Issues
- Catalog dependency resolver + validation tool

## Phase 3: Tier A Web Installer

- Web wizard (no CLI on host)
- Consumes sibling release ZIPs with prefilled install
- Path-prefix or subdirectory deployment guide

## Phase 4: Operations

- Backup/restore across suite
- Health dashboard aggregating sibling `/health`
- Upgrade path per catalog app version pin

## Not on this roadmap

- Product domain features (billing rules, CMS entities, archive compliance logic)
- Monorepo merge of sibling repositories
- Shared application database

Last updated: 2026-05-29

# Roadmap

NeNe Suite — **installer and orchestrator** for NeNe sibling products.
See [ADR 0002](./adr/0002-orchestrator-not-application-monolith.md).

## North Star

Operators install only the NeNe apps they need, once, with shared org context and apex navigation —
while each product remains independently installable.

## Editions (direction — ADR 0015, draft)

Beyond self-hosting, NeNe Suite is heading toward **two editions** (proposed in
[ADR 0015](./adr/0015-suite-hosted-multi-tenant-mode.md)):

- **Self-hosted (OSS)** — `docker compose up`, no ads, operator owns everything.
- **Hosted — "NeNe Cloud Free"** (`free.nene-suite.com`) — multi-organization,
  ad-supported, continued use OK; the acquisition funnel for install support and
  bespoke work. Headline is **anti-lock-in / data portability** ("無料で始める。
  必要になったら、いつでも自社サーバーへ。"), not price. The sibling apps are already
  multi-tenant; the remaining work is the Suite identity/registry layer. Phases 2–4
  below are read in light of this direction.

## Phase 0: Governance and Foundation ✅

- Governance docs, ADR 0001–0005, catalog stub ✅
- Orchestration compliance (士業 review pattern) ✅ Issue #8
- 税理士 / 公認会計士 + 弁護士 sign-off on binding docs ✅ (2026-05-31)

## Phase 1: Tier B Installer MVP ✅ (mostly)

- Docker Compose orchestrator ✅ (multi-stage image incl. SPA; entrypoint
  auto-migrates — ADR 0014)
- Select Invoice + Clear; provision DBs + env + service token ✅
- Apex shell (login + app links + install wizard + audit viewer) ✅
- Staging on ConoHa VPS with automatic deploy from `main` ✅
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
- Shared application database **across products** (each app keeps its own DB;
  hosted multi-tenancy is per-app `organization_id` scoping, not a shared app DB)

Last updated: 2026-06-21

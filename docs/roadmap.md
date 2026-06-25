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
  multi-tenant; the Suite identity/registry layer is now built — organizations /
  memberships / roles / superadmin console (multi-tenant **Phase A**) and the
  federation IdP key plane (ES256 + JWKS, **B1**, edition-gated). The remaining
  hosted work is sibling-side org resolution + the assertion flow (B2), portability
  round-trip (B5), signup/abuse, and ADR 0015 acceptance (B6). Phases 2–4 below are
  read in light of this direction; the detailed build-out lives in
  [`docs/milestones/2026-06-multi-tenant-suite.md`](./milestones/2026-06-multi-tenant-suite.md).

## Phase 0: Governance and Foundation ✅

- Governance docs, ADR 0001–0005, catalog stub ✅
- Orchestration compliance (士業 review pattern) ✅ Issue #8
- 税理士 / 公認会計士 + 弁護士 sign-off on binding docs ✅ (2026-05-31)

## Phase 1: Tier B Installer MVP ✅

- Docker Compose orchestrator ✅ (multi-stage image incl. SPA; entrypoint
  auto-migrates — ADR 0014)
- Select Invoice + Clear; provision DBs + env + service token ✅
- Apex shell (login + app links + install wizard + audit viewer) ✅
- Staging on ConoHa VPS with automatic deploy from `main` ✅
- Control DB + provisioning are PostgreSQL-capable as well as MySQL ✅ (ADR 0016)
- Document add-app workflow (remaining minor doc task)

## Phase 2: Federation Contract — in progress

- ✅ Suite identity/registry layer — organizations, memberships, roles, superadmin
  console + active-org switcher; session JWT carries `org_external_id` + role
  (multi-tenant Phase A; ADR 0015 §8).
- ✅ Federation IdP key plane — ES256 assertion issuer/verifier, `/.well-known/jwks.json`,
  signing-key store + rotation/revoke, edition-gated (B1; ADR 0012).
- ✅ Catalog dependency resolver + validation tool (`AppDependencyResolver`,
  `tools/validate-catalog.sh`).
- ✅ Origin consumption contract fixed — signed static GETs + detached-JWS
  verification for update / announcements / house-ads (ADR 0017).
- ✅ Suite Origin **consumption client** — profiled-TUF read model: detached-JWS
  (EdDSA) verification with conformance-corpus parity, per-product `gen` watermark,
  and the update / announcements / house-ads read APIs + dashboard wiring
  (O0–O5b, epic #230; ADR 0017 consumer). Disabled-degrade until the trust anchor
  is configured.
- ⏳ Shared JWT issuer / org UUID **propagation into siblings** — org resolution +
  authorization-code assertion flow, tracked via cross-repo Issues (B2).
- ⏳ Upgrade **orchestration** — version-compare vs the installed version +
  dependency-ordered "update all". **Suite orders / gates / relays only; the apply
  stays with each sibling's own Tier A** (Origin ADR 0001 §5 / ADR 0013). Backlog
  epic #251; first prerequisite is installed-version tracking.

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

Last updated: 2026-06-25

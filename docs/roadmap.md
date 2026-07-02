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
- Repository is **public**; professional (士業) review is **advisory** — consolidated before a public
  release, not a per-change gate (ADR 0003 / 0005, amended 2026-06-27) ✅

## Phase 1: Tier B Installer MVP ✅

- Docker Compose orchestrator ✅ (multi-stage image incl. SPA; entrypoint
  auto-migrates — ADR 0014)
- Select Invoice + Clear; provision DBs + env + service token ✅
- Apex shell — **responsive left-sidebar** nav (full / icon-rail / off-canvas drawer) with login +
  app links + install wizard + an **audit viewer (before/after diff detail + evidence CSV)** ✅
- **In-app help** — glossary, per-screen guides, tutorials, and inline annotations for non-technical
  operators, behind a Help nav entry ✅ (ADR 0024)
- Staging on ConoHa VPS with automatic deploy from `main` ✅
- Control DB + provisioning are PostgreSQL-capable as well as MySQL ✅ (ADR 0016)
- ⏳ **App database topology** — generalize the implicit single model (same server, suite
  creates, catalog-id name) to a per-app **database target**: `provision` | `adopt` modes +
  configurable server. Default target = today's behavior (unchanged); MVP adds **external-server
  adopt** (register an existing DB, no DDL) for "bring an existing tool under the suite" — the
  data-plane companion to ADR 0012 §8. Contract **accepted** as **ADR 0021** (§3 invariant holds:
  one DB per app, no shared schema, no cross-DB writes). Engine + manifest recording **shipped**
  (#280, #284); adopt works via env end-to-end.
- ⏳ **App onboarding modes** — the operator-facing adopt entry, contracted as **ADR 0022** (accepted):
  one onboarding model with two entry modes — **mode A** suite-driven install adopt (implementable now;
  a dedicated `PUT /install-sessions/{id}/database-targets` + wizard step) and **mode B** standalone-first
  inbound join (ADR 0012 §7/§8; depends on B2). The database target is decoupled from the install-session
  so both modes feed one path (no A→B rework). **Mode A shipped** (backend #292, frontend #296).
- ⏳ **Post-install database re-adoption** — change an app's database target after install. The app
  **self-diagnoses** a candidate database (a sibling **preflight** contract: generic, read-only, never
  Suite-named) and the suite orchestrates + records — the app stays the legitimacy judge (suite stays
  app-agnostic). Contract **accepted** as **ADR 0023**: HMAC-signed `adoption_token` (TOCTOU),
  credentials never on the wire (env-allowlist candidate → SSRF closed), register-only / reversible,
  deployment-driven apply (ADR 0019). **Read-only first** (Admin "Databases" view + preflight); the
  live re-adopt write is a later slice.
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
- ✅ Installed-version tracking + **catalog version mirror** — the sibling auth-gated
  `/machine/health` `version` probe (NENE2 v1.5.330) feeds the update diff, and the catalog API
  mirrors `installedVersion` / `availableVersion` per app (ADR 0013 §4, **accepted**). Sibling
  adoption is cross-repo (nene-invoice#496 / nene-clear#182 / nene-records#586 — all still open
  as of 2026-07-02, so live installations report `unknown` until the siblings wire the probe).
- ⏳ Shared JWT issuer / org UUID **propagation into siblings** — org resolution +
  authorization-code assertion flow, tracked via cross-repo Issues (B2).
- ⏳ **Federated user lifecycle** — prompt deprovisioning beyond JIT-on-login: a pull
  lifecycle delta feed (SCIM-shaped) + best-effort back-channel logout (OIDC-shaped) so a
  suite-side disable / role-revoke / delete takes effect across member tools. Contract
  **accepted** as **ADR 0020** (extends ADR 0012; no cross-DB writes; NENE2 gets a generic
  framework feature, never Suite-named). A B2 follow-on (depends on B1 keys + B2 org resolution
  + the ADR 0012 §5 roster-pull surface).
- ⏳ **MFA / step-up** — generic TOTP primitive in NENE2 (no new auth repo; NENE2#1427 —
  **shipped in NENE2 v1.5.333**), enforced at the Suite IdP when federated (the assertion carries a
  step-up claim) and available to standalone siblings; **decoupled** from the federation roadmap.
  Contract **accepted** as **ADR 0025** (extends ADR 0012). The remaining work is Suite-side
  (IdP enforce + step-up claim). NeNe Clear's MFA question (#341 / nene-clear#195) is answered.
- ⏳ Upgrade **orchestration** — dependency-ordered "update all", **deployment-driven** (Tier B:
  Suite recreates each sibling container at the new image in dependency order with min-version
  gating; the sibling migrates on boot — its own Tier A). **Suite drives deployment; the apply stays
  the sibling's** (Origin ADR 0001 §5 / ADR 0013 §3/§8 / ADR 0014 / **ADR 0019**, which supersedes the
  mis-specified ADR 0018). Backlog epic #251; the prerequisites (installed-version tracking, catalog
  version mirror, ADR 0013 acceptance, ADR 0019 orchestration contract) have landed — the Suite
  deployment-driven orchestrator + apex "update all" UI are the remaining work (no sibling runtime
  endpoint; NENE2#1416 withdrawn).

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

Last updated: 2026-07-02

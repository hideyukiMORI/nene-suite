# ADR 0015: Suite Hosted Multi-Tenant Mode & Product Editions

## Status

proposed (draft, 2026-06-21)

This ADR is a **draft**. The commitment gate (Open Questions §1) is **decided —
the owner is doing this as a business** (2026-06-21). The legal re-review (§7) is
now a **launch** gate (batched before `free.nene-suite.com` goes public), not an
acceptance gate. Acceptance still awaits the remaining open questions (signup/abuse,
org-resolution choice) and the terminology registration landing.

## Context

NeNe Suite was designed as a self-hosted orchestrator: one install, one
organization, with [ADR 0012](./0012-federation-participation-contract.md)
freezing **suite mode = one organization** and the terminology registry even
prohibiting the phrase that describes one suite serving many organizations.

Two realities pull against that single-organization assumption:

1. **Users will run a "demo" as production.** A genuinely useful product (NeNe
   Invoice for a sole proprietor, a small studio, a freelancer, a workshop) gets
   adopted as-is. A public try-it surface on one shared organization would let
   one company see another company's invoices — a reputational failure that no
   disclaimer survives. Financial data makes this non-negotiable.

2. **The sibling apps are already multi-tenant from the foundation — the Suite is
   not.** This is the key finding (verified 2026-06-21 across the sibling
   repositories):

   - The reference implementation is **NeNe Records** (per-request organization
     resolution, `Role`/`Capability` enums, `CapabilityMiddleware`,
     `organization_id` on tenant-scoped tables).
   - **NeNe Invoice ADR 0006 (accepted)** — "multi-tenant from the foundation":
     every tenant-scoped table carries `organization_id`; the `organizations`
     table is the tenant; an `OrgResolverMiddleware` resolves the organization
     **before** authorization; resolution modes are `single` (default), `path`,
     `subdomain`, `custom_domain`; **every repository query is org-scoped**; a
     security assessment confirmed SQL-level cross-organization isolation. The
     role hierarchy is `superadmin` (cross-tenant, `organization_id` may be
     `NULL`, holds `manage_organizations`) → `admin` → `member` → `viewer`.
   - **NeNe Payout** (binding multi-tenancy doc) states a single installation may
     serve one organization or **many (NeNe Suite)** — the siblings were written
     anticipating the Suite driving them for multiple organizations.
   - **NeNe Corpus ADR 0005** adopted "shared DB + `organization_id` filter".
     NeNe Contact (ADR 0006), NeNe Concierge (ADR 0004), and NeNe Serve (ADR 0006)
     carry the same premise; NENE2 itself teaches request-scoped tenant isolation.

So "single organization" is not an app constraint — it is only the apps' default
**resolution mode**. The mismatch is entirely on the Suite side. The Suite's
control schema today is a flat `operators` table (id, email, password_hash,
display_name, timestamps) with **no organization, membership, or role concept**,
and its session JWT carries only `sub` + `suite_id` (no `org_external_id`, no
role). The expensive, risky part — tenant-isolated financial data — is already
built and reviewed in the apps. What is missing is the Suite identity/registry
layer needed to run them for many organizations.

## Decision (proposed)

### 1. Two product editions

NeNe Suite is offered in two editions (the GitLab.com / Mattermost / WordPress.com
pattern):

- **Self-hosted (OSS).** `docker compose up` on the operator's own host. No ads.
  The operator owns all data, compliance, and operations. Paid install support
  and bespoke development are the business.
- **Hosted — "NeNe Cloud Free".** A vendor-operated, multi-organization service.
  Free, ad-supported (house-ads via [ADR 0013](./0013-update-aggregation-and-upgrade-orchestration.md)
  entitlement), continued real use permitted. Acts as the acquisition funnel:
  try → grow → **export → self-host** → install support → custom development.

**Surfaces (subdomains).** The hosted surface is **`free.nene-suite.com`**, not
`demo.*` — "demo" implies trial / throwaway / not-for-production, while "free"
implies a free plan that may be used continuously, which matches this decision.
Three surfaces are reserved, started in order:

| Subdomain | Role | When |
| --- | --- | --- |
| **`free.nene-suite.com`** | NeNe Cloud Free — multi-org, ad-supported, continued use OK | first |
| `demo.nene-suite.com` | No-signup hands-on trial, sample data, periodic reset | later |
| `app.nene-suite.com` | Future cloud core incl. paid tiers | future |

The screen must always label it (e.g. **"NeNe Cloud Free / 無料クラウド版 / 広告あり /
自己ホスト移行可"**) so expectations do not drift.

### 1.1 Positioning — lead with portability ("自己ホスト移行可"), not "free"

The headline is **anti-lock-in / data portability**, not price. The differentiator
is that — uniquely among comparable services — a NeNe Cloud Free organization can
**export its data and move to its own self-hosted install at any time**. This is
not bolted on: the sibling products already carry this DNA (NeNe Invoice's roadmap
headline is *"Japan SMB billing without SaaS lock-in"*). Cloud Free amplifies what
the parts already promise.

This inverts the usual SaaS funnel: the **exit is the funnel**, not churn. A user
who outgrows Free exports and self-hosts, and that migration is exactly where paid
install support and bespoke development are sold. Monetization is therefore
**ads (free tier) + services (install support / bespoke) + future paid cloud** —
deliberately **not** subscription lock-in.

The **canonical taglines** (adopted as Suite's official copy — recorded in
`docs/explanation/product-vision.md`) frame *starting* and *moving*, not price:

> 無料で始める。必要になったら、いつでも自社サーバーへ。
>
> データはあなたのもの。

Ads stay **house-ads only** (ADR 0013) — never ad-targeting on tenant financial
data, or the "your data is yours" promise dies.

**This makes data portability a build commitment, not a tagline (see §5.1).**

### 2. Realign the single-organization default (supersede ADR 0012 in part)

This ADR **supersedes the "suite mode = one organization" default of
[ADR 0012](./0012-federation-participation-contract.md)** for the hosted edition.
Suite mode gains a multi-organization deployment in which one Suite installation
hosts many organizations. Self-hosted single-organization remains the default and
is unchanged. ADR 0012's federation contract (asymmetric assertions, JWKS,
`org_external_id` as the federation key, separate sibling local sessions) is
**reused, not replaced** — it already carries an `org_external_id` claim, which
is exactly what a multi-organization hub needs.

### 3. The Suite becomes the organization registry + memberships + platform plane

The Suite control schema is extended to mirror the apps' tenancy model:

- An **`organizations`** registry (the Suite-side roster authority — name, slug,
  `external_id` = `org_external_id`, plan/entitlement, status). This is the
  identity/roster side only; never sibling domain data (ADR 0012 §11).
- **Memberships** linking an operator to one or more organizations with a role.
- A **`superadmin` platform plane** for the vendor operator (cross-organization,
  manages organizations and plans). The apps already define `superadmin`; the
  Suite is its natural home.
- The session JWT is extended to carry the resolved `org_external_id` and a role
  claim (today it carries only `sub` + `suite_id`).

### 4. Drive the apps' organization resolution; do not reinvent it

The Suite provisions each organization and drives the **already-existing** app
resolution modes — `subdomain` or `custom_domain` for hosted, `path` where
appropriate — and propagates `org_external_id` via the federation assertion. The
apps keep enforcing isolation at the repository layer exactly as they do now. The
Suite adds **routing + identity**, not a new isolation mechanism.

### 5. Isolation strategy — shared-schema multi-tenancy (aligned with the apps)

Because the apps are built as **shared-DB + `organization_id` filter** with
SQL-level enforcement and a security review, the aligned hosted isolation model
is **shared-schema multi-tenancy** (logical isolation by `organization_id`). This
reuses the apps' existing machinery rather than fighting it.

Per-tenant **physical** isolation (a dedicated instance/database per organization)
is **not** the default; it is retained only as a future option for a premium /
heightened-compliance tier. (An earlier draft of this discussion preferred
physical isolation as "safer"; that is withdrawn now that the apps' logical
isolation is confirmed built and reviewed.)

Caveat: the invoice security assessment is round 1. Cross-organization isolation
of financial data must be treated as a **continuous** guarantee (defense in depth
beyond a single `WHERE` clause; cross-tenant regression tests), not fire-and-forget.

### 5.1 Data portability (export → self-host import) is a launch prerequisite

Because §1.1 makes "自己ホスト移行可" the headline, a real, **tested whole-organization
export → self-host import** path must exist before that claim is published — a
broken "migrate anytime" is worse than not promising it. Today only **CSV
import/export** exists at the app level (e.g. NeNe Invoice ADR 0011 / csv-import-design,
NeNe Clear csv-export); a clean whole-org round-trip (the reserved-but-unwritten
NeNe Invoice **ADR 0017 "export/import-install"** — its number is skipped between
0016 and 0018) is **not yet built**. Finishing that round-trip, per app, is a
**prerequisite to launching NeNe Cloud Free with the portability headline**, and is
the single most strategically important dependency this ADR introduces.

### 6. Entitlement & ads

Free vs paid (ads-on vs `ads_off`) reuses the
[ADR 0013](./0013-update-aggregation-and-upgrade-orchestration.md) entitlement
propagation. NeNe Cloud Free is the ad-supported free tier; self-hosted and paid
tiers are ads-off.

### 7. Compliance & liability posture change (gating)

Operating a hosted service that holds customers' personal and financial data
makes the vendor a **data custodian / processor**. The existing binding posture
(`disclaimer.md`, orchestration-compliance, the 2026-05-31 法務 / 士業 sign-offs)
was written for the **self-hosted** operator who owns their own compliance. The
hosted edition changes that, so it requires a **legal re-review** (西村法律事務所,
already on record) scoped to data custody, retention, breach notification under
個人情報保護法, and the limits of disclaiming liability when real use is foreseeable.

**Review timing (owner decision 2026-06-21): batched, not per-change.** Confirming
with counsel on every change would stall development. The legal re-review is run
**once, near the end — before public launch of `free.nene-suite.com`** — as a
consolidated gate, not a per-PR checkpoint. It is a precondition of *launch*, not
of accepting this draft or of building behind a flag.

### 8. Non-goals

- The Suite still does not store sibling domain data (invoices, payments,
  reconciliation) — roster + identity only (ADR 0012 §11 unchanged).
- Self-hosted single-organization behavior is unchanged; no existing operator is
  forced into the hosted model.
- This ADR does not retrofit tenancy into the apps — that work is already done in
  the sibling repositories and is referenced, not redone here.

## Open questions

1. ~~**Commitment gate:** does the owner commit to operating NeNe Cloud Free as a
   real service?~~ **Decided 2026-06-21 — yes** ("ビジネスとしてやるなら 1 を選ぶ
   しかない"). The owner commits to operating it, with the ongoing duties (backup/DR,
   個人情報保護法 安全管理措置, availability, support, abuse/bot handling) accepted as cost.
2. Signup / onboarding / org-creation flow and abuse prevention (bots, throttling).
3. Org-resolution choice for hosted: `subdomain` vs `custom_domain` (vs `path`).
4. NeNe Cloud Free persistence/retention policy (persistent SaaS); `demo.*` is the
   reset-on-schedule surface (§1).
5. Whether physical isolation (§5) is ever offered, and to whom.

## Terminology registry impact (required on acceptance — ADR 0006)

To register when this ADR is accepted (same PR):

- The Suite **`organizations`** registry, **membership**, and **role** identifiers
  (aligned with the apps' `organization_id` / `org_external_id`; the prohibited
  `TENANT_ID` / `suite_org_id` spellings stay prohibited).
- Any new `NENE_SUITE_*` variables for edition/hosting mode and the hosted
  organization-resolution configuration.
- A canonical prose term for "one Suite hosting many organizations" (the phrase
  `multi-tenant suite` remains prohibited; choose an approved term, e.g.
  "hosted multi-organization mode").

## Consequences

**Benefits.** The hardest part of a hosted SaaS — tenant-isolated financial data —
already exists and is reviewed in the apps, so the hosted edition is far more
feasible than a from-scratch multi-tenant build. The acquisition funnel (free
cloud → self-host → support → bespoke) and the ad model are coherent and reuse
ADR 0013. Self-hosted users are unaffected.

**Costs / new Suite work.** Organization registry + memberships + roles in the
control schema; `superadmin` platform console; JWT/role propagation; signup &
org provisioning driving app resolution; entitlement/ads wiring; and — the real
ongoing cost — operating a service (DR, security, support, compliance).

**Risks.** The vendor becomes a data custodian (legal re-review gates acceptance).
Shared-schema isolation of financial data demands continuous rigor. Superseding
ADR 0012's single-org default touches a governance-signed contract and must be
sequenced so self-hosted/dev paths are unaffected.

## Related

- Issue: `#123`
- Supersedes (in part): [ADR 0012](./0012-federation-participation-contract.md)
  (suite mode = one organization default). Reuses ADR 0012's federation
  assertion / JWKS / `org_external_id`.
- [ADR 0002](./0002-orchestrator-not-application-monolith.md) (orchestrator, not
  monolith), [ADR 0004](./0004-suite-environment-contract.md) (env contract),
  [ADR 0011](./0011-control-database-url-resolution.md) (control DB),
  [ADR 0013](./0013-update-aggregation-and-upgrade-orchestration.md) (entitlement
  / house-ads), [ADR 0014](./0014-schema-migration-lifecycle.md) (migration
  lifecycle).
- Sibling multi-tenancy prior art (already accepted, referenced not redone):
  NeNe Invoice ADR 0006, NeNe Records (reference implementation), NeNe Payout
  ADR 0004 / 0018, NeNe Corpus ADR 0005, NeNe Contact ADR 0006, NeNe Serve ADR 0006.
- Portability prior art / dependency (§5.1): NeNe Invoice "without SaaS lock-in"
  roadmap + ADR 0011 (CSV import) + reserved ADR 0017 (export/import-install, not
  yet written); NeNe Clear CSV export. The whole-org export→self-host round-trip is
  a launch prerequisite.
- `docs/explanation/disclaimer.md`, `docs/explanation/orchestration-compliance.md`
  (liability posture); `docs/explanation/sign-off-legal-2026-05-31.md` (legal
  re-review trigger).
- Superseded by: none.

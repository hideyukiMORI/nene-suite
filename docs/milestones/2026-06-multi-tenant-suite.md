# Milestone: Suite Multi-Tenant Foundation (2026-06)

Goal: bring the **Suite identity/registry layer** up to multi-organization, so one
Suite installation can host many organizations (NeNe Cloud Free). The sibling apps
are already multi-tenant ([ADR 0015](../adr/0015-suite-hosted-multi-tenant-mode.md));
the gap is the Suite. Today the Suite has a flat `operators` table and a session
JWT carrying only `sub` + `suite_id` — no organization, membership, or role.

**Status: M1 + M2 complete** (autonomous build, 2026-06-21). The full,
dependency-verified build-out order is now recorded below (see
**"Recommended build-out order (sequencing review — 2026-06-21)"**). The M3
audit-vocabulary fork is **resolved — Option A (add `organization` + `membership`)**.
Steps that change auth behavior (A6 / M4) still must not auto-merge unreviewed.

## Approach

Small, independently CI-green slices behind no behavior change until wired. Each
slice mirrors the existing NENE2 slice pattern (entity + repository interface +
PDO repository + `database/schema/*.sql` + phinx migration + service-provider
wiring + PHPUnit, with an in-memory test double). No HTTP surface until the data
model is in place, so the OpenAPI contract and route↔spec tests are untouched
early on.

## Milestones

### M1 — Organizations registry (control DB) — data layer ✅ (PR #132)

- [x] `organizations` table: `id` (ULID), `external_id` (= `org_external_id`,
      unique), `name`, `slug` (unique), `status` (active/disabled — soft-disable
      only, ADR 0012 §5), timestamps.
- [x] `Organization` entity, `OrganizationStatus` enum,
      `OrganizationRepositoryInterface`, `PdoOrganizationRepository`,
      `database/schema/organizations.sql`, phinx migration, `TenancyServiceProvider`
      wiring, PHPUnit (+ in-memory double).

### M2 — Memberships + roles — data layer ✅ (PR #134)

- [x] `Role` enum: `superadmin` (cross-tenant, platform), `admin`, `member`,
      `viewer` (mirrors NeNe Invoice ADR 0006 / NeNe Records), with
      `isPlatform()` / `requiresOrganization()`.
- [x] `memberships` table: `id`, `operator_id`, `organization_id` (NULL =
      platform/superadmin), `role`, timestamps; unique `(operator_id,
      organization_id)`.
- [x] `Membership` entity, repository (+ PDO + in-memory), schema, migration,
      wiring, PHPUnit.

### M3 — Organization use cases (no HTTP yet) — ▶ unblocked 2026-06-21 (Option A); see A0–A4

- [ ] `CreateOrganization` use case (mint `external_id`, slug validation,
      before/after audit per ADR 0007).
- [ ] `GrantMembership` use case (operator ↔ org + role).
- [ ] Bootstrap: first operator becomes a platform `superadmin` **and** `admin` of the
      default org (so a self-hosted single-org session has an org context — ADR 0015 §8). See A4.

> **Decision needed before M3 (audit vocabulary — ADR 0006 / 0007).** These are
> suite mutations, so each must record a before/after audit event. The audit
> `entity_type` is a **closed enum** kept consistent across four places:
> `docs/openapi/openapi.yaml` (`AuditEntityType`), `schema/suite-audit-event.schema.json`,
> the generated `frontend/src/shared/api/schema.gen.ts` (via `npm run codegen`),
> and `docs/explanation/audit-trail.md` §4. M3 needs either **new values**
> (`organization`, `membership`) added to all four, **or** a decision to **reuse
> the existing `suite_org_profile`** entity type. This is a governance choice, so
> M3 was not auto-merged.
>
> **Resolved 2026-06-21 — Option A: add `organization` + `membership`** (do not
> reuse `suite_org_profile`). Rationale and the exact edit sites are in §1 of the
> recommended build-out order below. This is step **A0**, the first unblocker.

### M4 — Wire into auth (behavior change — careful)

- [ ] Session JWT carries `org_external_id` + role for the resolved org.
- [ ] Membership lookup at login; org selection when an operator has many.

### M5 — HTTP surface (OpenAPI contract)

- [ ] superadmin org-management endpoints (create/list/soft-disable) + role
      guard; OpenAPI spec + route↔spec test + before/after audit.

## Recommended build-out order (sequencing review — 2026-06-21)

Authoritative, dependency-verified sequence (3 independent strategist plans +
adversarial dependency/regression critique, 2026-06-21). It refines the coarse
M1–M5 above into ordered steps. Mapping: **M3 ≈ A0–A4**, **M4 ≈ A6**,
**M5 ≈ A7**; A1, A1.5, A4.5, A5, A8 are steps the coarse plan left implicit.

### §1. Decision — audit vocabulary (the M3 fork): Option A — add `organization` + `membership`

Do **not** reuse `suite_org_profile` (bound to one cosmetic action
`org_display_name.changed`, no code emitter) and do **not** hybrid. Reuse would
collide `entity_id` semantics (org id vs membership id vs display string under one
type) and make six security-relevant cross-tenant lifecycle actions
indistinguishable from a name tweak in the compliance evidence surface; the
membership half has no vocabulary at all. The closed-enum edit must happen once
regardless.

Register in A0: `organization` → `organization.created` / `.renamed` / `.disabled`;
`membership` → `membership.granted` / `.revoked` / `.role_changed`. Reconcile the
pre-registered `apex_operator.role_changed` (`audit-trail.md` §4) as superseded by
`membership.role_changed`. Leave `suite_org_profile` untouched (declared-but-unused,
harmless). Edit sites: `docs/openapi/openapi.yaml` (`AuditEntityType`),
`schema/suite-audit-event.schema.json`, `frontend/src/shared/api/schema.gen.ts`
(regenerate via `npm run codegen` — never hand-edit), `docs/explanation/audit-trail.md`
§4, `docs/explanation/terminology.md`; tick `docs/review/compliance.md`.

### §2. Phase A — registry foundation (self-hosted-safe; no public exposure; no ADR 0015 acceptance needed)

| id | Ships | Why here (forcing dependency) | Behavior change | Gate / review |
| --- | --- | --- | :---: | --- |
| **A0** | Audit vocab: add `organization` + `membership` across the 4 sites + reconcile `apex_operator.role_changed`; regen `schema.gen.ts` | A2/A3 emitters need a legal closed-enum type | No | compliance + terminology sign-off; CI drift check (openapi↔schema↔gen) |
| **A1** | `TransactionRunner` unit-of-work (begin/commit/rollback over Nene2 `DatabaseQueryExecutor`) + in-memory double; retrofit `CreateOperatorUseCase` (today `save()`-then-`record()`) | New dual-writes must be born atomic (ADR 0007 §5); A4 needs operator+org+membership atomic | No | ADR 0007 §5; reentrant vs installer outer pseudo-txn |
| **A1.5** | Fail-closed signing key: `src/Http/JwtSecretResolver.php` removes the silent `nene-suite-dev-secret` fallback — a missing `NENE_SUITE_JWT_SECRET` aborts apex boot **immediately (no grace)** unless `NENE_SUITE_ALLOW_DEV_SECRET=1` (explicit dev opt-in); a serving-only entrypoint preflight enforces it at container start. Login rate-limit split to a follow-up (needs its own persistence design) | A7 exposes a role-gated superadmin surface; a forgeable hardcoded secret is a compromise even pre-public (all 3 critics: blocker/major) | Config | security review; staging/prod `.env.suite` must set the secret (operator action) |
| **A2** | `CreateOrganization` (ULID id + stable `external_id` UUID = ADR 0012 federation key), `RenameOrganization`, `DisableOrganization` (fills unreachable `OrganizationStatus.Disabled`, soft-only) — dark (not in `ROUTE_REGISTRARS`) | Memberships reference orgs; bootstrap/backfill need a create path; `external_id` format frozen here | No | ADR 0007 §5; ADR 0012 §5/§11 (no hard-delete) |
| **A3** | `GrantMembership` / `RevokeMembership` / `ChangeMembershipRole`; migration adding `UNIQUE(operator_id, organization_id)` + repo upsert/`updateRole` (today `save()` is INSERT-only); last-superadmin-revoke guard | Bootstrap grants a membership; role-change has no path today; NULL org is index-distinct so invariants live in the use case | No | ADR 0007 §5; Role invariant |
| **A4** | Installer bootstrap: after first operator, find-or-create default org (slug derived from `NENE_SUITE_ORG_NAME` via `OrgSlugDeriver`) + grant the operator **platform `Superadmin` AND default-org `Admin`** (a self-hosted single-org session needs an org context — ADR 0015 §8); stamp the session's `org_external_id` with the minted value so env/manifest/session/`organizations` row all agree; lookup-first idempotent re-run | Fresh installs must satisfy the org+membership invariant before any reader (A6); A4 is the org-id source of truth | Install-time only | ADR 0015 §8 no-regress; install re-run idempotency |
| **A4.5** | Extend `CreateOperatorUseCase` / `CreateOperatorHandler` to grant a default-org membership to every new operator | A 2nd self-host operator created via the existing endpoint would otherwise have zero memberships → A6 locks them out | No | ADR 0015 §8 |
| **A5** | **Backfill** for existing installs: idempotent entrypoint-invoked DI app command reusing A1+A2+A3 (not raw phinx); guard on **existence of pre-existing operators** (fresh DB = no-op); one membership per existing operator; org name = manifest body › `install_sessions.org_display_name` › literal default; emit real audit rows; `external_id` reconciled with A4 | **Lockout firewall — must complete before A6.** Runs on plain `docker compose up -d` via entrypoint (ADR 0014) before A6 code is reachable | No | ADR 0014 idempotent-on-every-start; multi-operator policy documented |
| **A6** | **M4 — JWT carries `org_external_id` + `role`** (the OSS behavior change): `CreateAuthSessionUseCase` resolves membership; `BearerTokenAuthenticator` returns a typed principal; server-side membership-resolution fallback when claims absent (24h TTL rollover) | Safe only because A4 + A4.5 + A5 guarantee every operator has a membership. **A5 must be merged + deployed first** | **Yes** | ADR 0012 claim naming; contract test (pre-A6 token still authorizes; `getAuthSession` shape preserved; zero-membership = clean fail-closed) |
| **A7** | **M5 — superadmin org/membership HTTP endpoints + OpenAPI**; wire Tenancy into `ROUTE_REGISTRARS`; authz from the A6 role claim; regen `schema.gen.ts` | First authenticated cross-tenant mutating surface — needs A6 role claim and A1.5 fail-closed secret | Yes | default-deny authz; 403 tests; OpenAPI↔gen drift |
| **A8** | Frontend superadmin console + org-switcher (inert/hidden at single membership) | Consumes A7 endpoints + regenerated types | Yes | ADR 0015 §8: single-org renders with no switcher (tested) |

**OSS self-host is fully functional at A8 and may stop there, never regressing (ADR 0015 §8).**

### §3. Phase B — hosted edition (edition-flagged off for OSS; ADR 0015 acceptance is the terminal gate)

| id | Ships | Why here | Gate |
| --- | --- | --- | --- |
| **B1** | Production auth remainder: asymmetric JWKS + token revocation + login rate-limit (fail-closed secret already in A1.5; rate-limit deferred here) | Reuse ADR 0012 JWKS; public-exposure prerequisite | ADR 0012 JWKS; security review; edition flag keeps self-host on env-secret HS256 |
| **B2** | App org-resolution + asymmetric federation assertion (`org_external_id`); choose subdomain vs custom_domain | Needs B1 keys; drive the apps' existing resolution modes, don't reinvent (ADR 0015 §4) | ADR 0012; ADR 0015 §4 open question |
| **B3** | Public signup + abuse controls (`free.nene-suite.com`) | Needs B1 hardening; reuses A2/A3; route flag default-off, depends on B6 (merged ≠ exposed) | ADR 0015 signup/abuse open question |
| **B4** | Entitlement/quota + house-ads (ADR 0013, house-ads only) | Needs signup/orgs to meter | ADR 0013; CI smoke: OSS build with all hosted flags off |
| **B5** | Whole-org export → self-host import round-trip; write + accept **NeNe Invoice ADR 0017** | Portability headline (ADR 0015 §5.1 launch prereq); needs B2 federation keys | tested round-trip is a launch prereq; import preserves `external_id` |
| **B6** | **Legal re-review (西村法律事務所, data custody / 個人情報保護法) + ADR 0015 acceptance** (resolve open questions, register terminology) | Terminal launch gate; fans in B3/B4/B5 | legal sign-off; do not lean on disclaimers/ToS |

### §4. Critical hard-ordering constraints

- **A0 before A2/A3** — emitters need a legal closed-enum type.
- **A1 before A2/A3** — new dual-writes born atomic (ADR 0007 §5); A1 retrofits `CreateOperatorUseCase` because A4 needs operator+org+membership atomic.
- **A1.5 before A7** — a role-gated superadmin surface on a forgeable hardcoded secret is a token-forgery compromise.
- **A2 before A3** — memberships reference organizations.
- **A4 + A4.5 + A5 all before A6** — fresh, newly-created, and existing operators must each hold a membership before the JWT/authenticator reads one. **Merging A6 before A5 = mass operator lockout** (the authenticator today reads only `sub`).
- **A4 is the org-id source of truth** — its minted `external_id` must be written back to `NENE_SUITE_ORG_EXTERNAL_ID`, or the runtime claim diverges from the row and breaks B2 federation.
- **A6 → A7 → A8**; **B1 before B2/B3**; **B5 + B6 before any public exposure** (B3's route flag depends on B6).

### §5. First three steps

1. **A0** — audit-vocab edit (Option A) + governance sign-off + `npm run codegen` + confirm the CI drift check. Zero-dependency; unblocks everything.
2. **A1** — `TransactionRunner` + retrofit `CreateOperatorUseCase` into one transaction (the A4 foundation). Parallelizable with A0.
3. **A1.5** — make a missing `NENE_SUITE_JWT_SECRET` a required-env boot error immediately (env-gated dev mode via `NENE_SUITE_ALLOW_DEV_SECRET`). Must precede A7. Login rate-limit deferred to a follow-up / B1.

Spine: **A0 / A1 / A1.5 (parallel roots) → A2 → A3 → A4 → A4.5 → A5 → A6 → A7 → A8**,
then **B1 → B2 → {B3, B5} → B4 → B6**.

## Out of scope here

- App-side tenancy (already done in the sibling repos).
- Whole-org export → self-host import (separate launch-prerequisite track,
  ADR 0015 §5.1).
- Entitlement/ads wiring (ADR 0013).
- Public signup / abuse prevention (ADR 0015 open question).

## Autonomous run log

- 2026-06-21: plan recorded (PR #130).
- 2026-06-21: **M1 organizations registry** merged (data layer; PR #132). +4 tests.
- 2026-06-21: **M2 memberships + Role enum** merged (data layer; PR #134). +8 tests.
- 2026-06-21: paused at M3 — needs the audit-vocabulary decision above (governance);
  not auto-merged. Full suite green throughout (92 tests).
- 2026-06-21: **sequencing review** (3 independent strategists + adversarial
  dependency/regression critique) — recorded the dependency-verified build-out
  order above; resolved the M3 fork to **Option A**; split production-auth so the
  OSS-relevant fail-closed signing key (A1.5) precedes the superadmin HTTP surface
  (A7); added the operator-lockout firewall (A4 + A4.5 + A5 all before A6 / M4).

## State for resuming

- New module `src/Tenancy/` (no HTTP, no behavior change yet): `Organization`,
  `OrganizationStatus`, `OrganizationRepositoryInterface` + `PdoOrganizationRepository`;
  `Membership`, `Role`, `MembershipRepositoryInterface` + `PdoMembershipRepository`;
  `TenancyServiceProvider` (wired in `ApplicationServiceProvider`). In-memory test
  doubles under `tests/Tenancy/`.
- Migrations `20260621000000_create_organizations_table` and
  `20260621000100_create_memberships_table`; schemas under `database/schema/`.
- Nothing reads these yet — `operators` / auth flow are unchanged. Safe to resume.
- Next: execute the recorded build-out order above, starting at **A0** (audit
  vocabulary — Option A) → **A1** (`TransactionRunner` + `CreateOperatorUseCase`
  retrofit) → **A1.5** (fail-closed JWT secret). The lockout firewall
  (A4 + A4.5 + A5) **must** land before **A6 / M4** (JWT carries `org_external_id`
  + role; behavior change — review).

Last updated: 2026-06-21

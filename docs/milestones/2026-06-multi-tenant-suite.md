# Milestone: Suite Multi-Tenant Foundation (2026-06)

Goal: bring the **Suite identity/registry layer** up to multi-organization, so one
Suite installation can host many organizations (NeNe Cloud Free). The sibling apps
are already multi-tenant ([ADR 0015](../adr/0015-suite-hosted-multi-tenant-mode.md));
the gap is the Suite. Today the Suite has a flat `operators` table and a session
JWT carrying only `sub` + `suite_id` — no organization, membership, or role.

**Status: M1 + M2 complete** (autonomous build, 2026-06-21). M3+ paused pending an
owner decision (see M3 note) — they touch audit vocabulary (governance) and auth
behavior, which should not be auto-merged unreviewed.

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

### M3 — Organization use cases (no HTTP yet) — ⏸ paused for owner decision

- [ ] `CreateOrganization` use case (mint `external_id`, slug validation,
      before/after audit per ADR 0007).
- [ ] `GrantMembership` use case (operator ↔ org + role).
- [ ] Bootstrap: first operator becomes `superadmin`.

> **Decision needed before M3 (audit vocabulary — ADR 0006 / 0007).** These are
> suite mutations, so each must record a before/after audit event. The audit
> `entity_type` is a **closed enum** kept consistent across four places:
> `docs/openapi/openapi.yaml` (`AuditEntityType`), `schema/suite-audit-event.schema.json`,
> the generated `frontend/src/shared/api/schema.gen.ts` (via `npm run codegen`),
> and `docs/explanation/audit-trail.md` §4. M3 needs either **new values**
> (`organization`, `membership`) added to all four, **or** a decision to **reuse
> the existing `suite_org_profile`** entity type. This is a governance choice, so
> M3 was not auto-merged; pick one and M3 proceeds.

### M4 — Wire into auth (behavior change — careful)

- [ ] Session JWT carries `org_external_id` + role for the resolved org.
- [ ] Membership lookup at login; org selection when an operator has many.

### M5 — HTTP surface (OpenAPI contract)

- [ ] superadmin org-management endpoints (create/list/soft-disable) + role
      guard; OpenAPI spec + route↔spec test + before/after audit.

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

## State for resuming

- New module `src/Tenancy/` (no HTTP, no behavior change yet): `Organization`,
  `OrganizationStatus`, `OrganizationRepositoryInterface` + `PdoOrganizationRepository`;
  `Membership`, `Role`, `MembershipRepositoryInterface` + `PdoMembershipRepository`;
  `TenancyServiceProvider` (wired in `ApplicationServiceProvider`). In-memory test
  doubles under `tests/Tenancy/`.
- Migrations `20260621000000_create_organizations_table` and
  `20260621000100_create_memberships_table`; schemas under `database/schema/`.
- Nothing reads these yet — `operators` / auth flow are unchanged. Safe to resume.
- Next: resolve the M3 audit-vocabulary decision, then M3 → M4 (JWT carries
  `org_external_id` + role; **behavior change — review**) → M5 (HTTP/OpenAPI).

Last updated: 2026-06-21

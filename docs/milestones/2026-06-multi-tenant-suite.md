# Milestone: Suite Multi-Tenant Foundation (2026-06)

Goal: bring the **Suite identity/registry layer** up to multi-organization, so one
Suite installation can host many organizations (NeNe Cloud Free). The sibling apps
are already multi-tenant ([ADR 0015](../adr/0015-suite-hosted-multi-tenant-mode.md));
the gap is the Suite. Today the Suite has a flat `operators` table and a session
JWT carrying only `sub` + `suite_id` — no organization, membership, or role.

**Status: in progress** (autonomous build, started 2026-06-21)

## Approach

Small, independently CI-green slices behind no behavior change until wired. Each
slice mirrors the existing NENE2 slice pattern (entity + repository interface +
PDO repository + `database/schema/*.sql` + phinx migration + service-provider
wiring + PHPUnit, with an in-memory test double). No HTTP surface until the data
model is in place, so the OpenAPI contract and route↔spec tests are untouched
early on.

## Milestones

### M1 — Organizations registry (control DB) — data layer

- [ ] `organizations` table: `id` (ULID), `external_id` (= `org_external_id`,
      unique), `name`, `slug` (unique), `status` (active/disabled — soft-disable
      only, ADR 0012 §5), timestamps.
- [ ] `Organization` entity, `OrganizationRepositoryInterface`,
      `PdoOrganizationRepository`, `database/schema/organizations.sql`, phinx
      migration, `TenancyServiceProvider` wiring, PHPUnit (+ in-memory double).

### M2 — Memberships + roles — data layer

- [ ] `Role` enum: `superadmin` (cross-tenant, platform), `admin`, `member`,
      `viewer` (mirrors NeNe Invoice ADR 0006 / NeNe Records).
- [ ] `memberships` table: `id`, `operator_id`, `organization_id` (NULL =
      platform/superadmin), `role`, timestamps; unique `(operator_id,
      organization_id)`.
- [ ] `Membership` entity, repository (+ PDO + in-memory), schema, migration,
      wiring, PHPUnit.

### M3 — Organization use cases (no HTTP yet)

- [ ] `CreateOrganization` use case (mint `external_id`, slug validation,
      before/after audit per ADR 0007).
- [ ] `GrantMembership` use case (operator ↔ org + role).
- [ ] Bootstrap: first operator becomes `superadmin`.

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

- 2026-06-21: plan recorded; starting M1.

Last updated: 2026-06-21

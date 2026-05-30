# ADR 0008: Inherit NENE2 Coding Standards — Binding for All Implementation

## Status

accepted

## Context

NeNe Suite Phase 1 will add PHP (NENE2 consumer), React apex/wizard UI, JSON
Schemas, and installer tooling. Without explicit coding rules, orchestration code
could drift from the portfolio standard established in NENE2 and proven in
nene-records / nene-invoice consumer projects.

The maintainer requires **strict adherence** to NENE2 conventions for:

- Naming, class roles, and file placement
- Handler → UseCase → Repository layering
- DI, validation layers, Problem Details errors
- Frontend module placement and data flow (TanStack Query, entity slices)
- JSON Schema and catalog structure
- Audit recording in use cases (`SuiteAuditRecorder`)

Copy-pasting sibling product domain rules is wrong — suite is orchestrator-only
(ADR 0002). Adaptation must be documented locally, not improvised at implementation time.

## Decision

1. **Inheritance map:** [`docs/inheritance-from-nene2.md`](../inheritance-from-nene2.md)
   defines upstream vs local ownership.

2. **Binding standards package** (all Phase 1+ code MUST comply):

   | Document | Scope |
   | --- | --- |
   | [`coding-standards.md`](../development/coding-standards.md) | Index + shared rules |
   | [`backend-standards.md`](../development/backend-standards.md) | PHP, API, installer use cases |
   | [`frontend-standards.md`](../development/frontend-standards.md) | Apex + wizard UI |
   | [`naming-conventions.md`](../development/naming-conventions.md) | PHP, TS, JSON, OpenAPI |
   | [`schema-conventions.md`](../development/schema-conventions.md) | Catalog + audit + manifest schemas |

3. **Namespace:** `NeNeSuite\{Domain}\` with domain-grouped folders — same
   pattern as NENE2 examples and nene-records — **never** layer roots.

4. **Framework dependency:** `hideyukimori/nene2` via Composer for HTTP runtime;
   do not reimplement middleware, routing, or Problem Details in suite `src/`.

5. **Self-review:** PRs touching implementation **MUST** name checklists from
   [`self-review.md`](../development/self-review.md) and `docs/review/`.

6. **Violations block merge** unless an ADR documents a time-boxed exception.

7. **Non-goals:** This ADR does not require Phase 1 to ship full frontend or
   apex API — it binds **how** code is written when those surfaces land.

## Consequences

**Benefits**

- AI and human contributors share one strict playbook aligned with NENE2.
- Reduces orchestration/domain boundary mistakes before installer MVP.
- Audit, manifest, and catalog schemas stay consistent with terminology SSOT.

**Costs**

- Larger doc surface to maintain when NENE2 upstream changes — sync via Issues.
- Stricter PR bar; placement mistakes fail review intentionally.

**Follow-up**

- Issue #9: wire `composer catalog:validate` in CI.
- Phase 1 scaffold: `composer.json`, `frontend/`, first domain module PR under these rules.

## Related

- Issue: #14
- [ADR 0002](./0002-orchestrator-not-application-monolith.md)
- [ADR 0007](./0007-suite-audit-trail-before-after.md)
- NENE2: `docs/development/coding-standards.md`
- nene-records: `docs/inheritance-from-nene2.md`, `docs/development/backend-standards.md`

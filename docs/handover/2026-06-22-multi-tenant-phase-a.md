# Handover: Multi-Tenant Foundation — Phase A complete (2026-06-22)

Status record and handover for the Suite multi-tenancy build-out. **Phase A (A0–A8b) is
complete and merged** (PRs #142–#168), deployed and live-verified on staging. This document
is the single entry point for whoever continues the work; it captures *what shipped*, *how
the code is organized*, *the invariants a maintainer must not break*, *how to run/extend it*,
and *what was deliberately deferred*.

Governing plan: [`docs/milestones/2026-06-multi-tenant-suite.md`](../milestones/2026-06-multi-tenant-suite.md).
Operating rules: [`AGENTS.md`](../../AGENTS.md). Driving ADR: [ADR 0015](../adr/0015-suite-hosted-multi-tenant-mode.md) (still **proposed/draft**).

---

## 1. Where we are

The Suite went from a flat `operators` table + a session JWT carrying only `sub` + `suite_id`
to a **multi-organization identity/registry layer**: organizations, memberships, roles, a
session that carries the active-org context, a superadmin-gated cross-tenant HTTP surface, and
a frontend superadmin console to drive it.

- **OSS single-org behavior is preserved** (ADR 0015 §8): a fresh install still works; the new
  surface is additive and superadmin-only.
- **The lockout firewall** (A4 installer bootstrap + A4.5 HTTP operator provisioning + A5
  serving-time backfill) landed *before* the first runtime behavior change (A6), so no existing
  operator can be locked out.
- Phase A went **one step past the recorded plan**: the milestone table documents through A8
  (org console + switcher); **A8b** (member-list endpoint + membership console UI) is an
  extension beyond it.

What is **not** done: the hosted/SaaS edition (Phase B), and the polish items in §7. ADR 0015
is still a draft — its acceptance is the terminal Phase B gate.

---

## 2. What shipped (A0–A8b)

Each row landed as an Issue → branch → PR → squash-merge (numbers are the **merge** PRs on
`main`). All passed the full CI gate; the runtime/contract steps (A6, A7) were additionally
design-aligned up front and adversarially/inline-reviewed before merge; A1.5/A5/A6/A7/A8 were
live-verified on staging.

| Step | What it delivered | PR |
| --- | --- | --- |
| (plan) | Dependency-verified build-out order pinned into the milestone doc | #140 |
| **A0** | Audit vocabulary: closed `entity_type` enum + `organization`/`membership` (Option A) | #142 |
| **A1** | `CreateOperator` made single-transaction (mutation + audit atomic, ADR 0007 §5) | #144 |
| **A1.5** | Apex JWT signing secret **fail-closed** (`JwtSecretResolver`, preflight) | #146 |
| **A2** | Organization use cases (Create/Rename/Disable) — data layer, no HTTP (dark) | #148 |
| **A3** | Membership use cases (Grant/ChangeRole/Revoke) + invariants (last-admin) — dark | #150 |
| **A4** | Installer bootstrap: default org + first operator as superadmin + org admin | #152 |
| **A4.5** | HTTP-created operators get a default-org `Admin` membership | #154 |
| **A5** | Idempotent serving-time tenancy backfill for existing installs | #156 |
| **A6** | Session JWT **and** auth-session response carry `org_external_id`/`role`/`superadmin`; typed principal + pre-A6 fallback (first runtime change) | #158 |
| **A7a** | Superadmin organization HTTP (create/list/rename/disable), default-deny | #160 |
| **A7b** | Superadmin membership HTTP (grant/change-role/revoke) | #162 |
| **A8a** | Frontend: surface session context + organization console | #164 |
| **A8b-1** | `GET /organizations/{id}/memberships` (enriched member list) | #166 |
| **A8b-2** | Frontend: membership console UI | #168 |

Final gate state: PHPUnit **228**, frontend vitest **33**, PHPStan L8 / php-cs-fixer /
`composer openapi` (22 operations) / Redocly / FSD eslint — all green.

---

## 3. Architecture map

Vertical-slice clean architecture on the vendored **NENE2** framework. 12 slices under
`src/`, each namespace `NeNeSuite\<Slice>`. Per operation, a slice has:
`<Verb><Noun>Input`/`Output`, `…UseCaseInterface` + `…UseCase`, `RepositoryInterface` +
`Pdo…Repository` (+ `…RepositoryFactory`), `<Verb><Noun>Handler` (HTTP adapter),
`…RouteRegistrar`, exception classes + `…ExceptionHandler`, `…View` serializers, and a
`…ServiceProvider`.

| Slice | Role |
| --- | --- |
| **Auth** | Apex operator identity + session. Login/self/logout, operator creation. Owns `BearerTokenAuthenticator`, `AuthenticatedPrincipal`, `OperatorSessionContextResolver`, `PasswordHasher`, operator repo + factory. |
| **Tenancy** | Multi-tenant core. Organizations + Memberships use cases/handlers, the `Role` enum, `SuperadminGuard`, repos + factories, the org/membership exception handlers. |
| **Installer** | One-time org bootstrap (`InstallerUseCase`, not HTTP) + idempotent serving-time `BackfillTenancyUseCase` + `BootstrapDefaultOrganizationUseCase`. |
| **SuiteAudit** | Append-only before/after audit trail (ADR 0007). Recorder writes on the mutation's connection; defines the `entity_type` emit sites; serves the read API. |
| **InstallSession** | Install-wizard state machine (start/get/disclaimer/complete/fail). |
| **AppCatalog / AppSelection / InstalledApps** | Catalog read, selection update, apex launcher list. |
| **DatabaseProvision / SuiteEnv / InstallManifest** | Per-app DB provisioning, env-file generation, install snapshots. |
| **Http** | Composition root: `RuntimeServiceProvider` (SUITE_ID, SUITE_ORG_EXTERNAL_ID, DB stack, JWT, PSR-17), `RuntimeContainerFactory`, `ControlDatabaseConfigResolver`, `JwtSecretResolver`. No domain logic. |

**Aggregation roots:**
- `src/ApplicationServiceProvider.php` — registers all 12 slice providers and exposes two
  container keys: `ROUTE_REGISTRARS` (8 registrars) and `EXCEPTION_HANDLERS` (15 handlers). No
  business logic; `instanceof`-validates every resolved service (a typo'd key → `LogicException`
  at boot).
- `src/Http/RuntimeServiceProvider.php` — runtime wiring consumed by `RuntimeApplicationFactory`.

**Frontend** (`frontend/`, Feature-Sliced Design): `app/` (router, providers, auth-gate),
`entities/` (auth, organization, membership, …), `features/` (organization-console,
membership-console, active-org-indicator, sign-in, …), `pages/` (admin/organizations, …),
`shared/` (api, i18n, ui, config). React 19 + react-router 7 + @tanstack/react-query 5 +
react-hook-form + zod; vitest + MSW.

---

## 4. Invariants & seams a maintainer MUST know

These are the non-obvious rules; breaking them passes a casual read but fails correctness.

1. **Connection-per-transaction (the #1 gotcha).** Write use cases call
   `DatabaseTransactionManagerInterface::transactional(fn(DatabaseQueryExecutorInterface $query) => …)`
   and **construct their repositories/recorder *inside* the closure** via the factory seams
   (`…RepositoryFactory::create($query)`). A repo injected at construction time runs on a
   **different** connection and will **not** roll back. The four seams:
   `OperatorRepositoryFactory` (Auth), `MembershipRepositoryFactory` + `OrganizationRepositoryFactory`
   (Tenancy), `SuiteAuditRecorderFactory` (SuiteAudit) — the audit row must commit/rollback with
   its mutation (ADR 0007 §5). Rationale is documented once in `OperatorRepositoryFactoryInterface`.

2. **Two authorization planes, never conflated.** `AuthenticatedPrincipal` carries
   `isSuperadmin` (platform/cross-tenant plane, gated by `SuperadminGuard`) **and** `role`
   (org-scoped). `role` is `admin`/`member`/`viewer` or null — **never** `Superadmin`
   (`BearerTokenAuthenticator` nulls it). A superadmin's membership row has
   `organization_id = NULL`.

3. **Default-deny is per-handler, first line.** There is **no** global auth middleware
   (intentional while few endpoints need auth). Every authenticated handler injects
   `BearerTokenAuthenticator` or `SuperadminGuard` and calls it as the **first statement** of
   `handle()`. `SuperadminGuard::ensure()` authenticates (401) *then* requires `isSuperadmin`
   (403). Adding an authenticated endpoint = add the guard call explicitly.

4. **A6 token compatibility.** `BearerTokenAuthenticator::principal()` reads the
   `org_external_id`/`role`/`superadmin` claims from an A6 token (detected with
   `array_key_exists`, because `false`/`null` are valid), and falls back to
   `OperatorSessionContextResolver::resolve(sub)` for a pre-A6 token so existing 24h tokens are
   not locked out. `operatorId()` still returns just `sub` (the stable original surface).

5. **Active-org resolution.** `OperatorSessionContextResolver` picks the **oldest** org-scoped
   membership as the active org (interactive switcher deferred); a membership pointing at a
   missing org degrades to "no active org"; zero memberships → `{null,null,false}` (login
   succeeds; role-gated calls 403 later, never 500). Platform-membership uniqueness (NULL org)
   is enforced **in application code** (`findByOperatorAndOrganization(op, null)`), because SQL
   treats NULL as distinct.

6. **Audit `entity_type`/`action` is a closed enum across FOUR sites.** Any new value needs one
   coordinated edit in the same PR: `docs/openapi/openapi.yaml` (`AuditEntityType`),
   `schema/suite-audit-event.schema.json`, the regenerated `frontend/src/shared/api/schema.gen.ts`
   (`npm run codegen`), and `docs/explanation/audit-trail.md` §4 (see
   `docs/development/schema-conventions.md`). This governance gate is why M3 was not auto-merged.

7. **A new HTTP route is a 3-part contract.** Register it in the slice `RouteRegistrar`, add the
   `operationId` to `openapi.yaml`, **and** add the `operationId` to
   `OpenApiContractTest::IMPLEMENTED_OPERATION_IDS` — or the route↔spec test fails in one
   direction.

8. **Wiring a new slice/handler.** Write the `ServiceProvider`, register it in
   `ApplicationServiceProvider::register()`, and (if it serves routes / throws domain exceptions)
   append its registrar/handlers to the `ROUTE_REGISTRARS` / `EXCEPTION_HANDLERS` closures —
   both branches, both `instanceof`-guarded.

9. **The serving-time backfill is idempotent + warn-and-continue.** `ops/docker/entrypoint.sh`
   runs (only for `apache2-foreground`): `phinx migrate` (30× retry, ADR 0014) → JWT fail-closed
   preflight → `backfill-tenancy.php`. The backfill runs on **every** boot, exits 0 even on
   failure, and must stay idempotent (guarded grants) and run after migrate. One-off commands
   (`php installer/install.php`) skip the block.

10. **Auth↔Tenancy is intentionally bidirectional.** Auth→Tenancy (A4.5/A6:
    `OperatorSessionContextResolver` reads Tenancy repos); Tenancy→Auth (A7/A8b: `SuperadminGuard`
    uses `BearerTokenAuthenticator`; the list-members use case reads `OperatorRepository`). The
    shared `Role` enum lives in Tenancy. DI resolves lazily, so there is no runtime cycle.

---

## 5. How to run, develop, and verify

**Ports** (CLAUDE.md, 88** band): Apex HTTP **8800** (`docker compose up suite`), MySQL control
DB **3389** (local inspection), Vite dev **5188** (`npm run dev` in `frontend/`). Never hardcode
80/3306.

**Backend gates** (mirror CI exactly — CI has no CI-only logic):
- `composer check` = `test` (`phpunit --testsuite NeNeSuite`) + `analyse` (PHPStan L8) + `cs`
  (php-cs-fixer dry-run; `composer cs:fix` applies) + `openapi` (`tools/validate-openapi.php`).
- ⚠️ **Local full-suite caveat:** a local `.env` defining `NENE_SUITE_CONTROL_DATABASE_URL`
  fails one assertion in `tests/Http/ControlDatabaseConfigResolverTest.php`. Move `.env` aside
  before a full local run; CI is clean. (See memory `project_local_phpunit_env_caveat`.)
  `phpunit.xml` sets `NENE_SUITE_ALLOW_DEV_SECRET=1` so a clean checkout doesn't fail closed.

**Frontend gates:** `npm run check` = `tsc` (strict) + `eslint --max-warnings 0` + `prettier
--check` + `vitest run` + `vite build`. After any `docs/openapi/openapi.yaml` change, run
`npm run codegen` and **commit** `src/shared/api/schema.gen.ts` — CI has a freshness git-diff
gate (it does not generate, it checks).

**CI** (`.github/workflows/ci.yml`, on PR + main push): three parallel jobs — *backend*
(PHPUnit/PHPStan/CS), *docs-and-contract* (terminology/links/catalog/Redocly), *frontend*
(codegen-freshness + check).

**Governed workflow** (AGENTS.md + `docs/ops/branch-protection.md`): `main` is protected by
ruleset `protect-main` (PR required, 3 CI checks required, no force-push/delete, **zero bypass
actors** — even admins cannot `--admin` merge or direct-push). Flow: create/reuse an Issue →
`feat/<issue>-<summary>` branch → Conventional Commit (English type/scope, Japanese body, `(#n)`)
→ PR → green CI → squash-merge `--delete-branch`. Repo docs are English.

**Staging** (`deploy-staging.yml`, auto after CI-success on main push): SSH to the VPS,
`git reset --hard origin/main`, `ops/staging/deploy-staging.sh` (`docker compose … -f
compose.staging.yaml up -d --build`, polls `/health`). The container serves on internal :80
behind a shared Caddy on the `edge` network. **Staging is operator-0 / schema-only:** migrations
run on every boot (entrypoint), but `install.php` (org bootstrap: first operator) is a one-time
per-environment step that has not been run there — so login isn't exercisable, and probes show
the firewall (no token → 401/403) rather than full flows.

---

## 6. How to extend (cookbooks)

- **Add a superadmin endpoint:** new `…Handler` (first line `$this->guard->ensure($request)`),
  register the route in the slice `RouteRegistrar`, add the op to `openapi.yaml` +
  `IMPLEMENTED_OPERATION_IDS`, wire the handler (and any new exception handler) in the
  ServiceProvider and `ApplicationServiceProvider`, `npm run codegen` + commit, add handler/use-case
  tests reusing `tests/Tenancy/OrganizationHttpTestSupport`.
- **Add a write use case:** build repos/recorder from the factory **inside** `transactional()`;
  emit a before/after audit row with a registered `entity_type`/`action`; test atomicity via a
  `DatabaseTestKit::sqlite()` transaction proof + an in-memory-double handler test.
- **Add a frontend entity:** mirror `entities/organization` (api-types from `schema.gen.ts`,
  model, mapper, query-keys, queries, mutations, barrel `index.ts`). Consume it only through the
  barrel from features/pages; do problem-detail mapping inside the feature hook (expose a
  `MessageKey`, never `AppError`) — `shared/api` and entity internals are eslint-forbidden to
  features/pages, and `shared/ui` may not import entities.
- **Add UI strings:** add to `en.ts` (source of truth) **and** `ja.ts` (the parity test checks
  ja). `fr/de/pt-BR/zh-Hans` are English-fallback stubs — do not extend the parity test to them
  (it would force backfilling the whole catalog). See memory `project_frontend_i18n_posture`.

---

## 7. Deferred / known gaps

Intentional Phase-A-skeleton limits, safe to ship, candidates for polish:

- **No list-operators endpoint** → membership *grant* takes a raw `operatorId` (ULID paste) in
  the UI; there is no operator picker.
- **Grant does not verify operator existence** (A7b; the A3 use-case docblock assigns referential
  checks to the console). Organization existence *is* checked (→404); a grant against a
  non-existent operatorId would still succeed.
- **Org-switcher is read-only** (a badge). There is no backend to re-scope the active org in the
  session JWT; the resolver always picks the oldest org-scoped membership.
- **Stale membership** (operator removed) shows `operatorId` instead of an email in the member
  list (the list endpoint degrades email/displayName to null).
- **i18n** is en + ja only; the other four locales fall back to English.
- **Milestone doc / run log** were stale through Phase A — git log is the source of truth for
  what shipped. (This PR updates the milestone doc status.)

---

## 8. Next steps

1. **Milestone doc** — mark Phase A complete (done in this change) and confirm the A-row mapping.
2. **Phase B (hosted edition)** — per the milestone B1–B6 and ADR 0015: asymmetric JWKS federation
   (supersede HMAC), entitlement/quota (ADR 0013), signup/abuse + org-resolution (ADR 0015 open
   questions), house-ads, and finally **ADR 0015 acceptance** (the terminal B6 gate).
3. **Polish** (any time) — the §7 gaps, most impactfully a list-operators endpoint + operator
   picker to make the membership console fully usable, and operator-existence validation on grant.

---

## 9. References

- Plan: [`docs/milestones/2026-06-multi-tenant-suite.md`](../milestones/2026-06-multi-tenant-suite.md)
- Rules: [`AGENTS.md`](../../AGENTS.md), [`docs/development/adr.md`](../development/adr.md),
  [`docs/ops/branch-protection.md`](../ops/branch-protection.md),
  [`docs/development/schema-conventions.md`](../development/schema-conventions.md)
- Ops: [`docs/ops/staging-deploy.md`](../ops/staging-deploy.md), `ops/docker/entrypoint.sh`,
  `.github/workflows/{ci,deploy-staging}.yml`
- ADRs (relevant): **0007** audit before/after (accepted), **0012** federation contract
  (accepted), **0014** schema migration lifecycle (accepted), **0015** hosted multi-tenant mode
  (**proposed/draft** — the terminal Phase B gate), **0013** update aggregation (proposed)
- Key code: `src/Tenancy/` (core), `src/Auth/{BearerTokenAuthenticator,OperatorSessionContextResolver}.php`,
  `src/ApplicationServiceProvider.php` (aggregation), `src/Http/RuntimeServiceProvider.php`,
  `tests/OpenApi/OpenApiContractTest.php` (contract gate),
  `frontend/src/{entities,features}/{organization,membership,*-console}`

_Last updated: 2026-06-22. Author: handover for the next maintainer of the Suite multi-tenancy work._

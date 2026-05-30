# Inheritance from NENE2

NeNe Suite inherits engineering governance and implementation conventions from
[NENE2](https://github.com/hideyukiMORI/NENE2). This document is the source of
truth for what is inherited, what is adapted for the orchestrator role, and what
is NeNe Suite–specific.

## Relationship

| Layer | Repository | Role |
| --- | --- | --- |
| Framework runtime | [NENE2](https://github.com/hideyukiMORI/NENE2) | HTTP runtime, DI, middleware, Problem Details, OpenAPI/MCP patterns |
| Orchestrator | **NeNe Suite** (this repo) | Installer, apex shell, catalog, env wiring, suite audit — **no domain SSOT** |
| Domain products | nene-invoice, nene-clear, nene-records, … | Billing, evidence, CMS, archive — each owns compliance and domain audit |

NeNe Suite is a **NENE2 consumer project** for the apex HTTP surface (Phase 1+),
not a fork of NENE2. Framework code stays in NENE2; orchestration code stays here.

## Inherited by policy (same rules)

Local copies live in this repository so agents and contributors do not guess.

| Topic | Local document |
| --- | --- |
| Issue-driven workflow | `docs/workflow.md` |
| Conventional Commits | `docs/development/commit-conventions.md` |
| Coding standards index | `docs/development/coding-standards.md` |
| Backend placement & layering | `docs/development/backend-standards.md` |
| Frontend placement & data flow | `docs/development/frontend-standards.md` |
| UI i18n message catalogs | `docs/development/i18n.md` |
| Naming (PHP + TypeScript) | `docs/development/naming-conventions.md` |
| JSON Schema conventions | `docs/development/schema-conventions.md` |
| Self-review before PR | `docs/development/self-review.md`, `docs/review/` |
| ADR operation | `docs/development/adr.md` |
| AI agent entry | `AGENTS.md` |
| Cursor summaries | `.cursor/rules/` |

**Enforcement:** violations of inherited placement, layering, naming, schema, or
security rules **block merge to `main`**. Exceptions require an ADR.

## Inherited by reference (framework behavior)

When implementing HTTP, middleware, validation, DI, or errors, follow NENE2
upstream docs unless NeNe Suite records an explicit deviation in an ADR.

| Topic | NENE2 upstream |
| --- | --- |
| Coding baseline | `docs/development/coding-standards.md` |
| Domain / use case layering | `docs/development/domain-layer.md` |
| HTTP runtime (PSR-7/15/17) | `docs/development/http-runtime.md` |
| Middleware order and security | `docs/development/middleware-security.md` |
| Request validation layers | `docs/development/request-validation.md` |
| Problem Details errors | `docs/development/api-error-responses.md` |
| OpenAPI conventions | `docs/integrations/openapi.md` |
| Database / Phinx | `docs/development/database-migrations.md` |
| Frontend integration | `docs/development/frontend-integration.md` |
| Quality tools | `docs/development/quality-tools.md` |
| Client project start | `docs/development/client-project-start.md` |

After `composer install`, treat `vendor/hideyukimori/nene2/docs/` as the live
framework reference. Sibling checkout at `../NENE2` for IDE navigation.

## Adapted for NeNe Suite

| Topic | NeNe Suite choice |
| --- | --- |
| Product goal | Meta installer + apex shell — orchestration only ([ADR 0002](./adr/0002-orchestrator-not-application-monolith.md)) |
| Namespace | `NeNeSuite\{Domain}\` |
| Domain modules | `InstallSession/`, `AppSelection/`, `SuiteEnv/`, `IntegrationWiring/`, `InstallManifest/`, `SuiteAudit/`, `AppCatalog/` — not billing/CMS |
| Control database | `nene_suite` DB for audit + install metadata only — separate from sibling app DBs |
| Public Problem Details base | `https://nene-suite.dev/problems/{problem-name}` (application errors) |
| Language policy | English for repo docs, OpenAPI, API errors; Japanese in Issues/PRs/commits |
| Binding compliance | `orchestration-compliance.md`, `audit-trail.md`, `terminology.md` — suite-specific |
| Tier B installer | Docker Compose + CLI under `tools/installer/` — not NENE2 Example apps |
| Catalog | `catalog/apps.json` + JSON Schema — not OpenAPI-owned product domains |

## NeNe Suite–specific (not inherited)

Record in ADRs or product docs when they stabilize:

- Install manifest file path and tamper-evidence format
- `SuiteAuditRecorder` and `suite_audit_events` schema ([ADR 0007](./adr/0007-suite-audit-trail-before-after.md))
- `NENE_SUITE_*` env contract ([ADR 0004](./adr/0004-suite-environment-contract.md))
- Catalog dependency resolution algorithm
- Tier A vs Tier B installer UX split

## When upstream and local docs conflict

1. Update the **local source-of-truth doc** in this repository first.
2. If the conflict is about **framework behavior**, prefer NENE2 upstream unless
   an ADR documents a deliberate deviation.
3. If the conflict is about **orchestration / SSOT / audit**, prefer NeNe Suite
   binding docs (`scope-contract.md`, `orchestration-compliance.md`, `audit-trail.md`).
4. Keep `.cursor/rules/` as a short summary; do not duplicate full policy text.

## Verification commands (Phase 1+ scaffold)

```bash
composer check
composer openapi
composer catalog:validate    # when script lands (Issue #9)
npm run check --prefix frontend   # when frontend/ exists
bash tools/check-terminology.sh
```

Until `composer.json` exists, standards PRs are verified by doc review,
`bash tools/check-terminology.sh`, and `git diff --check`.

Last updated: 2026-05-29

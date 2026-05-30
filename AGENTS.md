# Agent / AI Guide

Entry point for AI agents working on **NeNe Suite** (private repo `nene-suite`).

## Purpose (read first)

NeNe Suite is a **meta installer and orchestrator** — not a domain application.
It installs selected sibling products, writes suite environment variables, and
provides the apex login shell. Product APIs, schemas, and business rules stay in
each product repository.

See [ADR 0002](docs/adr/0002-orchestrator-not-application-monolith.md).

## Read First

- **NENE2 inheritance (binding):** `docs/inheritance-from-nene2.md` — ADR 0008
- **Coding standards index:** `docs/development/coding-standards.md`
- **Backend standards (binding):** `docs/development/backend-standards.md`
- **Frontend standards (binding):** `docs/development/frontend-standards.md`
- **Naming conventions (binding):** `docs/development/naming-conventions.md`
- **Schema conventions (binding):** `docs/development/schema-conventions.md`
- **i18n / message catalogs (binding):** `docs/development/i18n.md` — ADR 0009
- **Self-review:** `docs/development/self-review.md`, `docs/review/`
- **Scope contract (binding):** `docs/explanation/scope-contract.md`
- **Terminology (binding — exact spellings):** `docs/explanation/terminology.md`
- **Disclaimer (binding):** `docs/explanation/disclaimer.md` — no business or legal warranty
- **Orchestration compliance (binding):** `docs/explanation/orchestration-compliance.md` — SSOT / DB / 士業 review (ADR 0005)
- **Audit trail (binding):** `docs/explanation/audit-trail.md` — before/after for all suite mutations (ADR 0007)
- **Requirements:** `docs/explanation/requirements.md`
- **Suite env contract (binding):** `docs/explanation/suite-environment-contract.md` — ADR 0004
- **Product vision:** `docs/explanation/product-vision.md`
- **Installable apps:** `docs/integrations/sibling-products.md`
- **App catalog:** `catalog/apps.json`
- **API contract (Phase 1 SSOT):** `docs/openapi/openapi.yaml` — see `docs/review/openapi-contract.md`
- **Workflow:** `docs/workflow.md`
- **Commit conventions:** `docs/development/commit-conventions.md`
- **ADR policy:** `docs/development/adr.md`
- **Current work:** `docs/todo/current.md`
- **Roadmap:** `docs/roadmap.md`

## Operating Rules

- **Issue-driven** — create or reuse a GitHub Issue before substantive edits
- **No direct commits to `main`** — branch `type/issue-number-summary`
- **Conventional Commits** — English `type`/`scope`, Japanese description/body, `(#issue)` in subject
- **Do not vendor sibling product source** into this repository
- **Do not add product domain logic** (billing, CMS entities, document archive, etc.)
- **Do not imply business, legal, or compliance guarantees** — see `docs/explanation/disclaimer.md`
- **Use exact terms from `docs/explanation/terminology.md`** — typos and unregistered identifiers block merge (ADR 0006)
- **Follow NENE2-derived coding standards** — placement, layering, naming, schema rules block merge (ADR 0008)
- **Repository docs: English only**
- **No secrets** — never commit `.env`, tokens, or production credentials

## Framework boundary

Runtime products inherit [NENE2](https://github.com/hideyukiMORI/NENE2).
Suite may use PHP, shell, or Docker Compose for orchestration — choose per ADR when
implementation starts. Suite must not become a second NENE2 application monolith.

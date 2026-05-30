# Coding Standards

NeNe Suite coding standards split by surface. **Full policies live in the
dedicated documents below** — this file is the index.

| Surface | Source of truth |
| --- | --- |
| **PHP / API / installer runtime** | [`backend-standards.md`](./backend-standards.md) |
| **React / TypeScript apex & wizard UI** | [`frontend-standards.md`](./frontend-standards.md) |
| **Naming (PHP + TypeScript + JSON)** | [`naming-conventions.md`](./naming-conventions.md) |
| **JSON Schema & catalog** | [`schema-conventions.md`](./schema-conventions.md) |
| **NENE2 inheritance map** | [`../inheritance-from-nene2.md`](../inheritance-from-nene2.md) |

**Framework baseline:**
[NENE2 coding standards](https://github.com/hideyukiMORI/NENE2/blob/main/docs/development/coding-standards.md)
— NeNe Suite deviates only where local docs or ADRs say so.

**Binding ADR:** [ADR 0008](../adr/0008-inherit-nene2-coding-standards.md).

---

## Shared rules (all surfaces)

- GitHub Issue-driven work; focused PRs; no direct commits to `main`
- **Strict typing** — PHP `declare(strict_types=1);`, readonly DTOs; TypeScript `strict`
- **Orchestrator only** — no sibling domain logic ([`scope-contract.md`](../explanation/scope-contract.md))
- **OpenAPI** describes apex/admin JSON API; catalog uses JSON Schema
- **Audit** — mutating orchestration actions record `before_json` / `after_json` ([`audit-trail.md`](../explanation/audit-trail.md))
- **Terminology** — exact spellings from [`terminology.md`](../explanation/terminology.md) (ADR 0006)
- **Placement violations block merge** — see backend and frontend standards
- Public docs, OpenAPI text, schema descriptions, API error metadata: **English**
- Issues, PRs, commits, `.cursor/rules/`: **Japanese allowed**

---

## Backend (summary)

Full policy: **`docs/development/backend-standards.md`**.

- NENE2 consumer — framework in `vendor/`, orchestration in `src/`
- Domain-grouped modules (`InstallSession/`, `SuiteAudit/`, …) — **not** `Controllers/`, `UseCases/`
- Handler → UseCase → RepositoryInterface → PdoRepository
- `SuiteAuditRecorder` in use cases — same transaction as mutation when DB-backed
- No PDO/SQL outside `Pdo*Repository`; no business logic in handlers
- Phinx migrations for **`nene_suite` control DB** only — never sibling app tables
- PHPUnit: in-memory use cases, SQLite repositories, OpenAPI contract tests
- `composer check` before merge (Phase 1+)

---

## Frontend (summary)

Full policy: **`docs/development/frontend-standards.md`**.

- Phase 1+ apex shell and installer wizard UI
- React + TypeScript strict + TanStack Query + zero-tolerance module placement
- API first — server validates; browser UX validation only
- No domain SSOT labels invented in UI — copy from binding docs / API

---

## Schema & catalog (summary)

Full policy: **`docs/development/schema-conventions.md`**.

- `catalog/apps.json` validated against `catalog/apps.schema.json`
- Orchestration schemas under `schema/` with registered `$id`
- Field names registered in `terminology.md` before first use in code

---

## Verification (Phase 1+ scaffold)

```bash
composer check
composer openapi
composer catalog:validate
npm run check --prefix frontend
bash tools/check-terminology.sh
```

Until scaffold lands, standards docs are verified by review checklists and terminology script.

Last updated: 2026-05-29

# Backend API Self-Review

**Binding for Phase 1+ PHP.** Use for handlers, use cases, repositories, DI,
migrations, installer CLI PHP entries, and OpenAPI-backed apex API.

Sources: [`../development/backend-standards.md`](../development/backend-standards.md),
[`../development/naming-conventions.md`](../development/naming-conventions.md),
[`../inheritance-from-nene2.md`](../inheritance-from-nene2.md).

## Checklist

- [ ] Domain-grouped module under `src/{Domain}/` — no layer folders.
- [ ] Handler → UseCase → Repository only; handler does not call repository or audit directly.
- [ ] `declare(strict_types=1);` on all new PHP files.
- [ ] Input/Output DTOs are `final readonly`; use case has single `execute()`.
- [ ] Mutating use case records audit per [`../explanation/audit-trail.md`](../explanation/audit-trail.md); action registered in §4.
- [ ] `before_json` / `after_json` sanitized; no secrets; same transaction when DB-backed.
- [ ] SQL only in `Pdo*Repository`; parameterized queries only.
- [ ] Control DB only — no writes to sibling app domain tables.
- [ ] OpenAPI updated for new/changed endpoints; `operationId` camelCase.
- [ ] Tests: use case (in-memory), repository (SQLite), HTTP/contract as applicable.
- [ ] Phinx migration + `database/schema/{table}.sql` for new tables.
- [ ] Service provider uses explicit factories — no autowiring.
- [ ] Problem Details for errors; no stack trace leakage.
- [ ] `composer check` green (when scaffold exists).
- [ ] No orchestration-compliance or scope-contract violations.

Mark `N/A` only when genuinely not applicable (doc-only PR).

# Frontend Self-Review

**Binding for Phase 1+ frontend.** Use for apex shell, installer wizard, and admin audit UI.

Sources: [`../development/frontend-standards.md`](../development/frontend-standards.md),
[`../development/naming-conventions.md`](../development/naming-conventions.md).

## Checklist

- [ ] Layer imports respect `app → pages → features → entities → shared` — ESLint boundaries pass.
- [ ] Entities imported only via `entities/{resource}/index.ts`.
- [ ] No `fetch` outside `shared/api/client.ts`.
- [ ] TanStack Query in `queries.ts` / `mutations.ts`; mappers inside entity hooks.
- [ ] Components use model types — not raw DTOs or generated OpenAPI types in features.
- [ ] Wizard disclaimer step server-confirmed before complete; no client-only bypass.
- [ ] No forbidden marketing strings ([`../explanation/terminology.md`](../explanation/terminology.md) §12).
- [ ] No secrets in env or browser storage; no JWT in `localStorage` without ADR.
- [ ] Feature hook test with MSW for new features.
- [ ] `npm run check --prefix frontend` green.
- [ ] OpenAPI/codegen updated if API shapes changed.

Mark `N/A` for doc-only PRs.

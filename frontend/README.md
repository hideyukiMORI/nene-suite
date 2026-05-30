# NeNe Suite frontend

Apex shell (login, launcher) and installer wizard UI — a thin client over the
suite JSON API. Strict layered architecture per
[`docs/development/frontend-standards.md`](../docs/development/frontend-standards.md):
`app → pages → features → entities → shared` (no upward imports, enforced by
ESLint `import/no-restricted-paths`).

```bash
npm install
npm run check     # type-check + lint + format + test + build
npm run dev       # Vite dev server (proxies /api, /health to the suite)
npm run codegen   # regenerate src/shared/api/schema.gen.ts from ../docs/openapi/openapi.yaml
```

## Layout

| Layer | Owns |
| --- | --- |
| `shared/` | HTTP client, errors, env, i18n, generated OpenAPI types |
| `entities/` | One API resource: `api-types` / `model` / `mapper` / `query-keys` / `queries` / `mutations` (barrel `index.ts`) |
| `features/` | Workflows (`hooks/` + `ui/`) — consume entities, never raw `fetch`/DTOs |
| `pages/` | Route wiring |
| `app/` | Providers, router, auth gate, error boundary |

- **All user-visible strings** via `useTranslation()` / `t('key')` — catalogs in
  `src/shared/i18n/messages/` ([`docs/development/i18n.md`](../docs/development/i18n.md)).
- Feature hooks ship a Vitest + MSW test (`tests/msw/`, `tests/render/`).

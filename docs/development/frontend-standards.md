# Frontend Standards

NeNe Suite frontend is the **apex shell** (app launcher, session) and **installer
wizard UI** — thin clients over the suite JSON API. They are not the source of
truth for catalog, env wiring, manifest, or audit data.

**Status:** Phase 1+ scaffold under `frontend/`.

**Framework baseline:**
[NENE2 frontend integration](https://github.com/hideyukiMORI/NENE2/blob/main/docs/development/frontend-integration.md)
and [nene-records frontend standards](https://github.com/hideyukiMORI/nene-records/blob/main/docs/development/frontend-standards.md)
— NeNe Suite uses the **same layering and data-flow rules** with a smaller feature set.

**Inheritance map:** [`../inheritance-from-nene2.md`](../inheritance-from-nene2.md)

**Enforcement level:** placement, dependency direction, data flow, and security
violations **block merge to `main`**. Exceptions require an ADR.

---

## Document map

| Section | Covers |
| --- | --- |
| [Principles](#principles) | Non-negotiable values |
| [Architecture](#architecture) | Layers and import matrix |
| [Repository layout](#repository-layout) | Tree |
| [Module placement](#module-placement-zero-tolerance) | entities / features / shared |
| [Data flow](#data-flow) | Read/write paths |
| [Components](#component-and-hook-patterns) | React rules |
| [API access](#api-and-data-access) | client, TanStack Query, errors |
| [Installer wizard UX](#installer-wizard-ux) | Step flow, disclaimer gate |
| [Testing](#testing) | Vitest + MSW |
| [Security](#security) | Client baseline |
| [CI](#commands-and-ci) | npm scripts |

---

## Principles

| Principle | Meaning |
| --- | --- |
| **API first** | OpenAPI is the contract; UI reflects API types and Problem Details |
| **Unidirectional flow** | API → entity → feature → UI; events flow up via hooks/mutations |
| **Orchestrator UI only** | Launcher, install wizard, audit export — no Invoice/Clear domain screens |
| **Fixed placement** | Mandated paths — violations block merge |
| **Strict TypeScript** | `strict` + guards in `tsconfig.json` |
| **No magic styling** | Design tokens in `shared/ui/theme/` — no raw hex/spacing in features |
| **Fail closed** | 401 → login; disclaimer gate before install complete — no silent bypass |

---

## Architecture

Strict layered architecture: **`app → pages → features → entities → shared`**
(same as NENE2 starter / nene-records).

### Layer responsibilities

| Layer | Owns | Must not own |
| --- | --- | --- |
| `shared/` | HTTP client, theme, utils, env | Routes, workflows, resource models |
| `entities/` | One API resource: types, mappers, query keys, hooks | JSX, cross-resource orchestration |
| `features/` | User workflows (wizard step, app grid) | Raw `fetch`, DTO definitions |
| `pages/` | Route wiring, lazy loading | Business rules |
| `app/` | Providers, router, auth gate, error boundary | Feature-specific UI |

### Dependency graph (hard rule)

No upward imports (`entities → features`, `shared → entities`).

### Import matrix

Same as nene-records — see
[nene-records frontend standards § Import matrix](https://github.com/hideyukiMORI/nene-records/blob/main/docs/development/frontend-standards.md#import-matrix-mandatory).

Enforce with ESLint `import/no-restricted-paths` or `eslint-plugin-boundaries`.

---

## Repository layout

```text
frontend/
  package.json
  package-lock.json
  tsconfig.json
  vite.config.ts
  eslint.config.js
  vitest.config.ts
  src/
    app/
      providers.tsx
      router.tsx
      root-error-boundary.tsx
      auth-gate.tsx
    pages/
      home/                    # apex launcher
      install/                 # wizard shell
      admin/
        audit-events/          # optional Phase 2+
    features/
      app-launcher/
      install-wizard/
      disclaimer-ack/
    entities/
      installed-app/
      install-session/
      suite-audit-event/       # Phase 2+
    shared/
      ui/
      api/
      lib/
      config/
  tests/
    setup/
    msw/
    render/
```

Build output: `public_html/assets/` when integrated (NENE2 pattern).

---

## Module placement (zero tolerance)

### Canonical entity tree

Each API resource → `entities/{resource}/` (**kebab-case**, matches OpenAPI tag).

```text
entities/install-session/
  index.ts              # ONLY public import surface
  ids.ts
  api-types.ts
  model.ts
  mapper.ts
  query-keys.ts
  queries.ts
  mutations.ts
```

### Placement matrix

| Artifact | Path |
| --- | --- |
| Generated OpenAPI types | `shared/api/generated/` |
| Wire DTOs (pre-codegen) | `entities/{resource}/api-types.ts` |
| UI models | `entities/{resource}/model.ts` |
| HTTP transport | `shared/api/client.ts` |
| Problem Details parse | `shared/api/errors.ts` |
| Feature UI | `features/{feature}/ui/*.tsx` |
| Feature hooks | `features/{feature}/hooks/*.ts` |

### Forbidden placements

- DTOs in `features/` or `.tsx` files (except `*Props`)
- `fetch` outside `shared/api/client.ts`
- TanStack logic outside entity `queries.ts` / `mutations.ts`
- Domain copy implying statutory compliance (use API/disclaimer strings)
- Deep imports bypassing `entities/{resource}/index.ts`

---

## Data flow

### Read path

```text
API JSON → shared/api/client.ts → api-types → mapper → queries.ts → feature hook → UI
```

- Mappers run **inside entity hooks**, not components.
- Components receive **model** types — never raw DTOs.

### Write path

```text
UI event → feature hook → mutations.ts → client → API → invalidate query-keys → UI feedback
```

- Mutations live in `entities/*/mutations.ts`.
- Map Problem Details → `AppError` → user-safe message (English API metadata; UI strings via i18n when added).

### Wizard state

| State | Location |
| --- | --- |
| Current wizard step | route or `searchParams` (`/install?step=apps`) |
| Selected catalog ids | entity mutation + server session — **not** only localStorage |
| Disclaimer accepted | server-confirmed before `complete` mutation |
| Server install session | TanStack Query cache keyed by `installSessionKeys` |

Do not complete install client-side without successful API `install_session.completed`.

---

## Component and hook patterns

### Mandatory

| Pattern | Where |
| --- | --- |
| Hook + View | `features/*/hooks` + `features/*/ui` |
| Query key factory | `entities/*/query-keys.ts` |
| Barrel export | `entities/*/index.ts`, `features/*/index.ts` |
| Presentational UI | `shared/ui` — no `useQuery` in primitives |

### Feature example

```text
features/install-wizard/
  index.ts
  hooks/use-install-wizard.ts
  ui/InstallWizard.tsx
  ui/steps/AppSelectionStep.tsx
  ui/steps/DisclaimerStep.tsx
```

- `DisclaimerStep` calls entity mutation; does not set `localStorage` flag as source of truth.
- SSOT labels (Invoice billing, Clear evidence) come from **API or static copy approved in docs** — not invented in components.

### React rules

- Function components + hooks only; named exports only
- No `useEffect` + `fetch` — use TanStack Query
- No class components
- Storybook for `shared/ui` primitives (Phase 1+ scaffold)

Full TypeScript compiler options: same minimum set as nene-records
(`strict`, `noUncheckedIndexedAccess`, `verbatimModuleSyntax`, …).

See [`naming-conventions.md`](./naming-conventions.md) for file and symbol names.

---

## API and data access

- Single `apiClient` in `shared/api/client.ts`
- Attach auth per apex policy (cookie or bearer — ADR when implemented)
- Throw `AppError` from Problem Details on 4xx/5xx
- TanStack Query defaults in `app/providers.tsx`
- Regenerate types when `docs/openapi/openapi.yaml` changes:

```bash
npm run codegen --prefix frontend
```

---

## Installer wizard UX

Binding UX rules (compliance):

1. **Disclaimer step** — must call API ack before enable Complete; link to disclaimer copy from API or static asset path documented in OpenAPI.
2. **App selection** — show dependency hints from catalog API; disable invalid subsets client-side for UX, **enforce in use case**.
3. **Integration toggles** — Clear → Invoice off by default; explicit operator toggle (not hidden pre-check).
4. **No compliance marketing** — forbidden strings from [`terminology.md`](../explanation/terminology.md) §12.

Wizard **does not** write `.env` or secrets in browser storage.

---

## Testing

| Level | Tool | Required when |
| --- | --- | --- |
| Unit | Vitest | mappers, query-keys |
| Integration | Vitest + Testing Library + MSW | every feature hook |
| Component | Testing Library | wizard steps, launcher |
| Contract | MSW vs OpenAPI | endpoint touched |

- Query by role/label — not `data-testid` unless necessary
- `renderWithProviders` from `tests/render/`
- MSW handlers match OpenAPI shapes
- Bug fixes include regression test

Every new feature ships at least one **feature-hook test** with MSW (nene-records rule).

---

## Security

| Topic | Rule |
| --- | --- |
| Secrets | Never in frontend env except public `VITE_*` |
| Tokens | Prefer httpOnly cookies; no JWT in `localStorage` without ADR |
| XSS | No `dangerouslySetInnerHTML` without policy |
| Dependencies | `npm audit` — block high/critical on `main` |
| Fail closed | Unauthenticated users cannot trigger install complete |

---

## Commands and CI

```bash
npm install --prefix frontend
npm run check --prefix frontend
```

Recommended `check`: `type-check && lint && format && test && build`

CI on frontend PRs: `npm ci --prefix frontend && npm run check --prefix frontend`

---

## Non-goals

- Replacing sibling product admin UIs
- Client-side catalog JSON as SSOT (server reads `catalog/apps.json`)
- Duplicating orchestration logic skipped by API
- Alternate UI stack without ADR

---

## Related documents

- Self-review: `docs/review/frontend.md`
- Backend: `docs/development/backend-standards.md`
- Product: `docs/explanation/product-vision.md`
- Compliance copy: `docs/explanation/installer-disclaimer-copy.md`

Last updated: 2026-05-29

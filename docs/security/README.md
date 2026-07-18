# Security posture — NeNe Suite

Design-level record of the **authentication and authorization controls** in place
in NeNe Suite (the apex installer / orchestrator) and pointers to the automated
tests that guard them. This is an internal maintainer document, not a third-party
penetration test. Live-fire assessment (see the fleet precedent in NeNe Vault's
`docs/security/`) is future work (T3 L3/L4) pending maintainer go-ahead.

Scope note: this directory documents **controls that are implemented and
verified**. It deliberately does not enumerate un-remediated weaknesses or
exploitation detail — the repository is public. Working notes for hardening still
in flight are tracked out-of-band with the fleet hub.

## Authentication

| Control | Where | Guarded by |
|---|---|---|
| Apex operator JWT (bearer). Minted server-side; presented as `Authorization: Bearer`. | `src/Auth/CreateAuthSessionUseCase`, `BearerTokenAuthenticator` | `tests/Auth/CreateAuthSessionUseCaseTest`, `BearerTokenAuthenticatorTest` |
| Token stored in **`sessionStorage`** (tab-scoped, cleared on tab close) — **never `localStorage`** — to shrink the token's exposure window under XSS (fleet decision 2026-07-14). | `frontend/src/entities/auth/model.ts` | `frontend/…/auth/model.test.ts` (incl. *never writes to localStorage*, expiry → logged-out) |
| JWT secret resolution hard-fails in production on the dev-secret opt-in (no weak-secret fallback in prod). | `src/Http/JwtSecretResolver` → fleet `GuardedJwtSecretResolver` (#390/#391) | `tests/…/ControlDatabaseConfigResolverTest` |
| Expired session treated as logged-out; server-side revocation on logout. | `authStore.isAuthenticated()`, `RevokedTokenRepositoryInterface` | `tests/Auth/PdoRevokedTokenRepositoryTest` |
| Login-abuse resistance: per-client-IP velocity cap within a window → `429`. | `src/Auth/LoginRateLimiter` | `tests/Auth/LoginRateLimiterTest`, `LoginRateLimitedExceptionHandlerTest` |

## Authorization

Two independent dimensions (ADR 0012): a platform **`superadmin`** flag and an
**org-scoped role** (`admin` / `member` / `viewer`). Both default **fail-closed**.

| Control | Where | Guarded by |
|---|---|---|
| Frontend route guards fail closed: unauthenticated → `/login`, non-superadmin → `/`. | `frontend/src/app/auth-gate.tsx` | `frontend/…/app/auth-gate.test.tsx` |
| Session org-context fields fail closed when absent (pre-A6 sessions read as non-superadmin, no active org). | `frontend/src/entities/auth/mapper.ts` (`?? false` / `?? null`) | `frontend/…/auth/mapper.test.ts` |
| Backend superadmin gate + per-use-case authorization. | `src/Tenancy/SuperadminGuard`, use-case handlers | `tests/Auth/*`, `tests/Tenancy/*` |
| Federation trust plane: asymmetric JWKS signing keys, thumbprint-pinned; generate / rotate / revoke lifecycle with preflight. | `src/Auth/Federation*`, `JwkThumbprint` | `tests/Auth/Federation*Test`, `JwkThumbprintTest` |

## Output & error surface

- The SPA renders exclusively through **React's default escaping**; there is no
  `dangerouslySetInnerHTML` / `innerHTML` sink in the application code.
- API failures surface as **RFC 9457 Problem Details**; the transport guarantees a
  non-2xx response never reaches the SPA as raw HTML (no error-page HTML injection
  into the app). Guarded by `frontend/…/shared/api/errors.test.ts`,
  `shared/api/client.test.ts`.

## Frontend attack-surface inventory

A per-surface map of the browser-facing attack surface (XSS / injection / authz /
transport), the defense in place, and its test coverage lives in
[`frontend-attack-surface.md`](frontend-attack-surface.md). It is the T3 groundwork
sheet — an inventory of implemented controls and their coverage, not an
exploitation guide.

## Planned hardening (T3 roadmap)

Forward-looking, not yet landed:

- Automated **authorization-regression** coverage (a role × route matrix) built on
  the existing fail-closed guards.
- **Dependency audit** wired into CI (supply-chain signal).
- A **response-header baseline** for the served SPA.
- Live-fire assessment with a disposable harness (fleet precedent: Vault
  `docs/security/harness/`), against self-owned isolated environments only — never
  a production host — pending maintainer decision (L3/L4).

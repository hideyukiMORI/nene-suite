# ADR 0004: Suite Environment Contract (`NENE_SUITE_*`)

## Status

accepted

## Context

NeNe Suite enables **dual deployment**:

- **Standalone** — operator installs one product; that product's web installer owns auth and org setup.
- **Suite** — operator selects apps; suite provisions env, org UUID, sibling URLs, and optional shared JWT.

Sibling apps already use `NENE2_LOCAL_JWT_SECRET`, `organizations.external_id`, and per-app databases.
Without a documented env contract, each product would invent incompatible suite variables.

## Decision

### Mode switch

| Variable | Standalone | Suite |
| --- | --- | --- |
| `NENE_SUITE_MODE` | unset or `0` | `1` |

When `NENE_SUITE_MODE=1`, the app **must not** assume suite orchestration guarantees business outcomes
(see ADR 0003). It **may** read suite env for auth federation and sibling URL discovery.

### Variables written by NeNe Suite (orchestrator)

Suite installer generates these and writes them into each selected app's `.env` (and apex `.env`).

| Variable | Required (suite) | Description |
| --- | --- | --- |
| `NENE_SUITE_MODE` | yes | `1` when installed via suite |
| `NENE_SUITE_ID` | yes | Stable ULID/UUID for this suite **installation** (not org) |
| `NENE_SUITE_BASE_URL` | yes | Public origin with trailing slash, e.g. `https://example.com/` |
| `NENE_SUITE_APEX_URL` | yes | Apex shell URL, e.g. `https://example.com/` or `https://example.com/suite/` |
| `NENE_SUITE_ISSUER_URL` | yes | JWT issuer base used for apex login, e.g. `https://example.com/api/auth` |
| `NENE_SUITE_JWT_SECRET` | yes | Shared HMAC secret; suite sets **the same value** as `NENE2_LOCAL_JWT_SECRET` in each app unless a future ADR adopts JWKS. **Fail-closed (A1.5): a missing value aborts apex boot** unless `NENE_SUITE_ALLOW_DEV_SECRET` is set |
| `NENE_SUITE_ORG_EXTERNAL_ID` | yes | ULID/UUID stored in `organizations.external_id` at provision time |
| `NENE_SUITE_ORG_NAME` | recommended | Display name passed to each app's initial org record |
| `NENE_SUITE_INSTALLED_APPS` | yes | Comma-separated catalog ids, e.g. `nene-invoice,nene-clear` |
| `NENE_SUITE_ALLOW_DEV_SECRET` | no | Dev / local opt-in: permits the built-in dev JWT secret when `NENE_SUITE_JWT_SECRET` is unset. Never set in production / staging |

### Per-app public URL (sibling discovery)

For each installed catalog entry `{id}` with path segment `{path}`:

| Variable pattern | Example | Description |
| --- | --- | --- |
| `NENE_SUITE_APP_{SNAKE}_URL` | `NENE_SUITE_APP_NENE_INVOICE_URL=https://example.com/nene-invoice/` | Base URL for HTTP client calls |

`{SNAKE}` = catalog `id` uppercased with hyphens → underscores (`nene-invoice` → `NENE_INVOICE`).

Apps that call siblings read these variables; they **must not** hardcode suite paths.

### Variables unchanged (per product)

Each app keeps its **own** database credentials (`DB_*` or product-specific prefix from catalog).
Suite creates separate databases; it does not introduce a shared DSN.

### JWT claims (suite mode)

When apex (or delegated issuer) mints user tokens consumed by sibling apps:

| Claim | Type | Description |
| --- | --- | --- |
| `sub` | string | User email or stable subject id |
| `role` | string | Product role enum value |
| `org_id` | int \| null | **Local** organization primary key in the receiving app |
| `org_external_id` | string | Same as `NENE_SUITE_ORG_EXTERNAL_ID` |
| `suite_id` | string | Same as `NENE_SUITE_ID` |
| `iat`, `exp` | int | Standard time bounds |

Receiving apps map `org_external_id` → local `organizations.external_id` before trusting `org_id`.

Machine/service tokens (Clear → Invoice) remain **per-product** service API keys; suite may
generate and inject them during install but uses each app's existing env names (cross-repo Issue).

### Standalone behavior

When `NENE_SUITE_MODE` is unset or `0`:

- All `NENE_SUITE_*` variables are ignored if present.
- Local login and local web installer behave as today.
- `organizations.external_id` may remain null.

### Reference artifact

Non-secret example: [`.env.suite.example`](../../.env.suite.example) at repository root.
Binding field list: [`docs/explanation/suite-environment-contract.md`](../explanation/suite-environment-contract.md).

## Consequences

**Benefits.**

- One contract for suite installer and sibling implementers.
- Dual mode without forking product codebases.

**Costs.**

- Each catalog app needs a cross-repo Issue to read `NENE_SUITE_*` and map org federation.
- Path-prefix deployment requires correct `NENE_SUITE_APP_*_URL` generation.

**Follow-up.**

- Cross-repo Issues in Invoice, Clear, Records for env reader middleware.
- Catalog validation script should verify env key coverage (Issue TBD).

## Related

- Issue: `#6`
- ADR 0002 (orchestrator boundary)
- ADR 0003 (disclaimer)
- `catalog/apps.json`
- Supersedes: none
- Superseded by: none

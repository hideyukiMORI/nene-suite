# Suite Environment Contract (binding)

**Status: binding.** Field names and semantics match [ADR 0004](../adr/0004-suite-environment-contract.md).
Changing names or required fields requires an ADR amendment and sibling app coordination.

---

## Dual mode summary

```text
Standalone:  NENE_SUITE_MODE unset | 0  →  product installer owns everything
Suite:       NENE_SUITE_MODE=1          →  suite wrote NENE_SUITE_* + per-app DB_*
```

---

## Orchestrator variables

| Variable | Example | Notes |
| --- | --- | --- |
| `NENE_SUITE_MODE` | `1` | Off = standalone semantics |
| `NENE_SUITE_EDITION` | `oss` | Product edition ([ADR 0015](../adr/0015-suite-hosted-multi-tenant-mode.md)): `oss` (default, self-hosted single-org) or `hosted` (vendor multi-org "NeNe Cloud Free"). **Fail-closed: anything but the exact string `hosted` is `oss`.** Gates the Phase B federation/IdP key plane (`oss` constructs none of it). Orthogonal to `NENE_SUITE_MODE` and `NENE_SUITE_ALLOW_DEV_SECRET` |
| `NENE_SUITE_ID` | `01JXXXXXXXXXXXXXXXXXXXX` | One per suite install on a host |
| `NENE_SUITE_BASE_URL` | `https://ops.example.com/` | Trailing slash required |
| `NENE_SUITE_APEX_URL` | `https://ops.example.com/` | Login / app launcher |
| `NENE_SUITE_ISSUER_URL` | `https://ops.example.com/api/auth` | Token mint endpoint base |
| `NENE_SUITE_JWT_SECRET` | `(random 32+ bytes hex)` | Copied to each app's `NENE2_LOCAL_JWT_SECRET`. **Fail-closed: if unset the apex refuses to boot** unless `NENE_SUITE_ALLOW_DEV_SECRET=1` (dev only) |
| `NENE_SUITE_ORG_EXTERNAL_ID` | `01JYYYYYYYYYYYYYYYYYYYY` | Federation key; equals `organizations.external_id`. On a pre-A4 upgrade the startup backfill (A5) seeds the org row's `external_id` from this value when it is a valid ULID; if it is malformed a fresh ULID is minted and the row diverges from the env (the serving backfill does not rewrite `.env`) — set the env to the row's `external_id` (or re-run install) and restart |
| `NENE_SUITE_ORG_NAME` | `Example KK` | Initial org display name |
| `NENE_SUITE_INSTALLED_APPS` | `nene-invoice,nene-clear` | Subset of catalog ids |
| `NENE_SUITE_ALLOW_DEV_SECRET` | `1` | **Dev / local only.** Permits the built-in dev JWT secret when `NENE_SUITE_JWT_SECRET` is unset. Never set in production / staging |

> **Fail-closed JWT secret (A1.5).** `NENE_SUITE_JWT_SECRET` is **required** to serve.
> If it is empty/unset the apex runtime refuses to boot — immediately, with no grace
> period — rather than sign sessions with the built-in development secret. A container
> entrypoint preflight (`ops/docker/preflight-jwt-secret.php`) enforces this at start, so a
> misconfigured deployment fails its health check instead of serving forgeable tokens. For
> local development only, set `NENE_SUITE_ALLOW_DEV_SECRET=1` to permit the dev secret. The
> installer writes a fresh random `NENE_SUITE_JWT_SECRET` on every run, so a completed install
> satisfies this.

### Suite control database (Phase 1+)

| Variable | Example | Notes |
| --- | --- | --- |
| `NENE_SUITE_CONTROL_DATABASE_URL` | `mysql://nene_suite:***@db/nene_suite` | **Suite only** — `suite_audit_events` + manifest metadata; not sibling app data. Resolution: [ADR 0011](../adr/0011-control-database-url-resolution.md) |

---

## Sibling URL variables

Pattern: `NENE_SUITE_APP_{CATALOG_SNAKE}_URL`

| Catalog id | Variable | Example path |
| --- | --- | --- |
| `nene-invoice` | `NENE_SUITE_APP_NENE_INVOICE_URL` | `https://ops.example.com/nene-invoice/` |
| `nene-clear` | `NENE_SUITE_APP_NENE_CLEAR_URL` | `https://ops.example.com/nene-clear/` |
| `nene-records` | `NENE_SUITE_APP_NENE_RECORDS_URL` | `https://ops.example.com/nene-records/` |

Trailing slash required. HTTP clients concatenate OpenAPI paths relative to this base.

---

## JWT claims (user sessions, suite mode)

| Claim | Purpose |
| --- | --- |
| `org_external_id` | Federation key across databases |
| `org_id` | Local PK in the app verifying the token |
| `suite_id` | Which installation issued the session |
| `sub`, `role`, `iat`, `exp` | Standard auth |

Apps **must** verify signature with `NENE2_LOCAL_JWT_SECRET` (same material as `NENE_SUITE_JWT_SECRET`).

---

## What suite env does not do

- Does not replace product-specific DB, mail, or storage config.
- Does not guarantee sibling API compatibility — catalog `requires` only documents install order.
- Does not certify compliance — see [`disclaimer.md`](./disclaimer.md).

---

## Example

See [`.env.suite.example`](../../.env.suite.example).

Last updated: 2026-06-21

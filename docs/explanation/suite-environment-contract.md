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
| `NENE_SUITE_ID` | `01JXXXXXXXXXXXXXXXXXXXX` | One per suite install on a host |
| `NENE_SUITE_BASE_URL` | `https://ops.example.com/` | Trailing slash required |
| `NENE_SUITE_APEX_URL` | `https://ops.example.com/` | Login / app launcher |
| `NENE_SUITE_ISSUER_URL` | `https://ops.example.com/api/auth` | Token mint endpoint base |
| `NENE_SUITE_JWT_SECRET` | `(random 32+ bytes hex)` | Copied to each app's `NENE2_LOCAL_JWT_SECRET` |
| `NENE_SUITE_ORG_EXTERNAL_ID` | `01JYYYYYYYYYYYYYYYYYYYY` | Written to `organizations.external_id` |
| `NENE_SUITE_ORG_NAME` | `Example KK` | Initial org display name |
| `NENE_SUITE_INSTALLED_APPS` | `nene-invoice,nene-clear` | Subset of catalog ids |

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

Last updated: 2026-05-29

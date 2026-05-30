# Sibling Products

NeNe Suite installs **sibling products** — independent repositories with their own databases,
compliance docs, and HTTP APIs. Suite does not replace their documentation.

> **Binding.** SSOT and integration rules below are governed by
> [`orchestration-compliance.md`](../explanation/orchestration-compliance.md).
> Domain compliance (tax, invoice, reconciliation, archive) lives in each sibling repo.

## Integration rules (portfolio-wide)

- **Separate databases** — no shared schema across apps (orchestration-compliance §3).
- **HTTP only** — no cross-app SQL, shared session store, or embedded domain libraries.
- **Organization federation** — `NENE_SUITE_ORG_EXTERNAL_ID` → `organizations.external_id`
  (IT identifier only — §4).
- **Service tokens** — machine-to-machine calls configured during install; scopes per sibling contract.

## System of record (SSOT)

```
NeNe Invoice  ←  system of record for billing, tax figures, payments on invoices
NeNe Clear      ←  system of record for bank evidence, reconciliation links, dunning log
NeNe Vault      ←  system of record for received-document archive
NeNe Profile    ←  CSV normalization output (downstream to Clear — not billing SSOT)
NeNe Records    ←  CMS / entity platform (optional catalog for Invoice Phase 4+)
```

NeNe Suite **MUST** preserve this matrix in installer summaries and docs.
Reference: [nene-invoice sibling products](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/integrations/sibling-products.md).

## Dependency direction (HTTP)

```
NeNe Clear    →  HTTP  →  NeNe Invoice /api/*     (read invoices, write payments — ADR 0009)
NeNe Profile  →  HTTP  →  NeNe Clear              (StandardTransaction — when integrated)
NeNe Invoice  →  HTTP  →  NeNe Records            (optional product catalog — Phase 4+)
```

Suite installs and wires env; it **does not** implement these APIs.

## Sibling compliance documents (domain — not suite)

| Product | Binding compliance doc |
| --- | --- |
| NeNe Invoice | [`accounting-compliance.md`](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/explanation/accounting-compliance.md) |
| NeNe Clear | [`payment-reconciliation-dunning-compliance.md`](https://github.com/hideyukiMORI/nene-clear/blob/main/docs/explanation/payment-reconciliation-dunning-compliance.md) |
| NeNe Vault | [`received-document-compliance.md`](https://github.com/hideyukiMORI/nene-vault/blob/main/docs/explanation/received-document-compliance.md) |

Installing multiple apps via suite **does not** merge these obligations. Each app
remains independently reviewable.

## Catalog entries

Authoritative list: [`catalog/apps.json`](../../catalog/apps.json).

| Catalog id | Repository | SSOT / role in suite |
| --- | --- | --- |
| `nene-invoice` | `hideyukiMORI/nene-invoice` | **Billing SSOT**; upstream for Clear |
| `nene-clear` | `hideyukiMORI/nene-clear` | Reconciliation evidence; requires Invoice |
| `nene-records` | `hideyukiMORI/nene-records` | CMS / entities |
| `nene-vault` | `hideyukiMORI/nene-vault` | Received-document archive |
| `nene-profile` | `hideyukiMORI/nene-profile` | Bank CSV normalization |
| `nene-corpus` | TBD | Content corpus (when repo exists) |

Status `planned` in the catalog means **not selectable** in the installer until runtime and release ZIP exist.

## Changes in sibling repos

When suite federation needs new env vars or install hooks, open an Issue in the **owning product repo** first, then update the catalog and suite orchestrator in this repo.

Last updated: 2026-05-29

# Sibling Products

NeNe Suite installs **sibling products** — independent repositories with their own databases
and HTTP APIs. Suite does not replace their documentation.

## Integration rules (portfolio-wide)

- **Separate databases** — no shared schema across apps.
- **HTTP only** — no cross-app SQL, shared Redis session store, or embedded libraries for domain data.
- **Organization federation** — suite org UUID stored as `organizations.external_id` in each app when suite mode is on.
- **Service tokens** — machine-to-machine calls (for example Clear → Invoice) configured during suite install.

## Catalog entries

Authoritative list: [`catalog/apps.json`](../../catalog/apps.json).

| Catalog id | Repository | Role in suite |
| --- | --- | --- |
| `nene-invoice` | `hideyukiMORI/nene-invoice` | Billing SSOT; upstream for Clear |
| `nene-clear` | `hideyukiMORI/nene-clear` | Reconciliation; requires Invoice API |
| `nene-records` | `hideyukiMORI/nene-records` | Flexible entity CMS |
| `nene-vault` | `hideyukiMORI/nene-vault` | Received-document archive |
| `nene-profile` | `hideyukiMORI/nene-profile` | Bank CSV normalization |
| `nene-corpus` | TBD | Content corpus (when repo exists) |

Status `planned` in the catalog means **not selectable** in the installer until runtime and release ZIP exist.

## Changes in sibling repos

When suite federation needs new env vars or install hooks, open an Issue in the **owning product repo** first, then update the catalog and suite orchestrator in this repo.

Last updated: 2026-05-29

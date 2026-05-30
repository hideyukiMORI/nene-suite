# Product Vision

NeNe Suite is the **deployment control plane** for the NeNe product family.

## Problem

Small businesses and agencies may run several NeNe products together — billing (Invoice),
reconciliation (Clear), CMS (Records), document archive (Vault), CSV normalization (Profile).
Installing each product separately repeats MySQL setup, admin creation, org provisioning,
JWT secrets, and sibling URL configuration. Operators want one guided setup with the option
to add apps later.

## Solution

A suite installer that:

1. Presents a checklist of available NeNe products.
2. Validates dependency order (for example Clear requires Invoice API).
3. Provisions one MySQL server with **separate database per app**.
4. Generates a shared **organization external ID** (UUID) for tenant federation.
5. Configures **suite mode** on selected apps.
6. Exposes an **apex shell** — login once, navigate to installed apps.

## Non-goals

- Replacing product admin UIs
- Shared database or cross-app SQL joins
- Embedding MCP servers (sibling products expose MCP via their own HTTP boundaries)
- Bundling proprietary third-party connectors

## Audiences

| Audience | Need |
| --- | --- |
| Shared-hosting operator (Tier A) | Web wizard, no CLI, release ZIPs |
| VPS / Docker operator (Tier B) | Compose stack, reproducible demo |
| Developer | Catalog schema, env contract, add-app workflow |

## Success criteria (Phase 1+)

- Install Invoice + Clear together with one org and working service token wiring.
- Standalone Invoice install unchanged when suite is not used.
- Documented `NENE_SUITE_*` contract consumed by at least two sibling apps.

Last updated: 2026-05-29

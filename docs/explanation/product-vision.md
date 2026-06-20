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

## Positioning & tagline (canonical)

The headline is **anti-lock-in / data portability**, not price: uniquely among
comparable services, a NeNe organization can export its data and move to its own
self-hosted install at any time. This is backed by reality (OSS, MIT, self-hostable;
NeNe Invoice's own roadmap says "without SaaS lock-in") and applies to the current
self-hosted product as well as the planned hosted edition (§ below).

**Official taglines (canonical copy — use verbatim):**

> 無料で始める。必要になったら、いつでも自社サーバーへ。
>
> データはあなたのもの。

(*"Start free. Move to your own server whenever you need to. Your data is yours."*)

Ads, where shown (hosted free tier), are **house-ads only** ([ADR 0013](../adr/0013-update-aggregation-and-upgrade-orchestration.md)) —
never ad-targeting on tenant data, or the "your data is yours" promise breaks.

## Product editions (direction — ADR 0015)

NeNe Suite is heading toward two editions (proposed in
[ADR 0015](../adr/0015-suite-hosted-multi-tenant-mode.md), still a draft):

- **Self-hosted (OSS)** — `docker compose up` on the operator's own host; no ads;
  the operator owns all data and compliance. Paid install support + bespoke
  development are the business.
- **Hosted — "NeNe Cloud Free"** (`free.nene-suite.com`) — vendor-operated,
  multi-organization, ad-supported, continued use OK; the acquisition funnel
  (try → grow → export → self-host → support → bespoke). The sibling apps are
  already multi-tenant; the remaining work is on the Suite side.

## Non-goals

- Replacing product admin UIs
- Shared database or cross-app SQL joins (each app keeps its own database; hosted
  multi-tenancy is per-app `organization_id` scoping, **not** a shared app DB)
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

## Disclaimer

Suite success criteria are **technical only**. Compliance or business readiness of
installed apps is explicitly out of scope — see [`disclaimer.md`](./disclaimer.md).

Last updated: 2026-06-21

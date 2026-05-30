# NeNe Suite

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![Private](https://img.shields.io/badge/status-private-lightgrey)]()

**Installer and orchestrator for multi-app NeNe deployments.**

NeNe Suite lets operators choose which NeNe products to install on one host,
provisions each app with separate databases, enables suite mode (`NENE_SUITE_MODE=1`),
and provides a shared login shell at the apex path. Individual products remain
installable standalone via their own git clone or release ZIP.

> **Meta product.** Suite does **not** replace product domain logic in
> [`nene-records`](https://github.com/hideyukiMORI/nene-records),
> [`nene-invoice`](https://github.com/hideyukiMORI/nene-invoice),
> [`nene-clear`](https://github.com/hideyukiMORI/nene-clear), or sibling repos.
> See [ADR 0002](./docs/adr/0002-orchestrator-not-application-monolith.md).

## Goals

- **Selective install** — choose apps during suite setup; install + initial config in one flow
- **Suite mode** — shared org UUID, JWT issuer, sibling URLs via environment (not shared DB)
- **Standalone parity** — each product still works without Suite when installed directly
- **Separate databases** — one MySQL database (or schema) per app; HTTP API for cross-app links
- **Tier B first** — Docker Compose orchestrator MVP; Tier A web installer follows product release ZIPs

## Documentation (read first)

| Topic | Document |
| --- | --- |
| Agent entry | [`AGENTS.md`](./AGENTS.md) |
| Scope contract (binding) | [`docs/explanation/scope-contract.md`](./docs/explanation/scope-contract.md) |
| Product vision | [`docs/explanation/product-vision.md`](./docs/explanation/product-vision.md) |
| Sibling / installable apps | [`docs/integrations/sibling-products.md`](./docs/integrations/sibling-products.md) |
| App catalog | [`catalog/apps.json`](./catalog/apps.json) |
| Workflow | [`docs/workflow.md`](./docs/workflow.md) |
| Commit conventions | [`docs/development/commit-conventions.md`](./docs/development/commit-conventions.md) |
| Current work | [`docs/todo/current.md`](./docs/todo/current.md) |
| Roadmap | [`docs/roadmap.md`](./docs/roadmap.md) |

## Repository status

**Phase 0 — Governance and product design.** Installer runtime not yet implemented.

## License

MIT — see [`LICENSE`](./LICENSE).

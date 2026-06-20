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

> **Disclaimer:** Suite assists with **environment setup only** — it does not guarantee
> business outcomes, legal compliance, or accounting correctness.
> See [`DISCLAIMER.md`](./DISCLAIMER.md) and [ADR 0003](./docs/adr/0003-installer-disclaimer-no-business-warranty.md).

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
| Terminology (binding) | [`docs/explanation/terminology.md`](./docs/explanation/terminology.md) |
| Disclaimer (binding) | [`docs/explanation/disclaimer.md`](./docs/explanation/disclaimer.md) |
| Orchestration compliance (binding) | [`docs/explanation/orchestration-compliance.md`](./docs/explanation/orchestration-compliance.md) |
| Requirements | [`docs/explanation/requirements.md`](./docs/explanation/requirements.md) |
| Suite env contract (binding) | [`docs/explanation/suite-environment-contract.md`](./docs/explanation/suite-environment-contract.md) |
| Product vision | [`docs/explanation/product-vision.md`](./docs/explanation/product-vision.md) |
| Sibling / installable apps | [`docs/integrations/sibling-products.md`](./docs/integrations/sibling-products.md) |
| App catalog | [`catalog/apps.json`](./catalog/apps.json) |
| API contract (Phase 1, SSOT) | [`docs/openapi/openapi.yaml`](./docs/openapi/openapi.yaml) |
| Workflow | [`docs/workflow.md`](./docs/workflow.md) |
| Commit conventions | [`docs/development/commit-conventions.md`](./docs/development/commit-conventions.md) |
| Current work | [`docs/todo/current.md`](./docs/todo/current.md) |
| Roadmap | [`docs/roadmap.md`](./docs/roadmap.md) |

## Repository status

**Phase 1 — Tier B installer MVP.** Docker Compose orchestrator, apex shell
(login + app launcher + install wizard + audit viewer), and all 13 Phase 1
OpenAPI operations are implemented. CI and automatic staging deployment to
ConoHa VPS (`suite-stg.nene-suite.com`) are live. Phase 0 governance, ADRs
0001–0013, and professional sign-offs are on record.

## License

MIT — see [`LICENSE`](./LICENSE).

# NeNe Suite

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](./LICENSE)
[![Public](https://img.shields.io/badge/status-public-brightgreen)]()

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

**Phase 1 (Tier B installer MVP) ✅ · multi-tenant Phase A + federation IdP key plane (B1) ✅ ·
Origin consumption client ✅.** Docker Compose orchestrator and a **responsive left-sidebar** apex
shell (login + app launcher + install wizard + an **audit viewer with before/after diff detail and
evidence-grade CSV**) with the Phase 1 OpenAPI operations; organizations / memberships /
roles + superadmin console; ES256 federation assertions + JWKS (edition-gated); and a profiled-TUF
Origin update / announcement / house-ad client. The **O6 upgrade-orchestration prerequisites** have
landed — installed-version tracking (via the sibling `/machine/health`), catalog version mirror, and
the upgrade contract (ADR 0013 + ADR 0019, deployment-driven). **Per-app database topology** and
**onboarding modes** (ADR 0021 / ADR 0022 — mode A suite-driven adopt shipped) plus **post-install
database re-adoption** (ADR 0023, accepted) extend the installer; an **in-app help system** —
glossary, per-screen guides, and tutorials for non-technical operators — ships behind a Help nav
entry (ADR 0024). **MFA / step-up** is decided (**ADR 0025**) — a generic TOTP primitive in NENE2 (no
new auth repo; **shipped in NENE2 v1.5.333**), enforced at the Suite IdP when federated and available
to standalone siblings. The **apex-shell UX-remediation B group is complete** (focus-trap / combobox
a11y, help-body locale signal, reversible org disable + re-enable, locale-toggle clamp —
#330/#332/#333/#334). CI and
automatic staging deployment
to ConoHa VPS (`suite-stg.nene-suite.com`) are live. Phase 0 governance, **ADRs 0001–0025**, and the
2026-05-31 professional sign-offs are on record. The
repository is **public**; professional (legal /
tax) review is **advisory** — consolidated before a public release, not a per-change gate (ADR 0003).

## License

MIT — see [`LICENSE`](./LICENSE).

# Contributing

NeNe Suite is built through small, Issue-driven changes. This document is the shared entry point for humans and AI agents.

## Required Reading

| Topic | Document |
| --- | --- |
| Agent entry point | `AGENTS.md` |
| Scope contract (binding) | `docs/explanation/scope-contract.md` |
| Terminology (binding) | `docs/explanation/terminology.md` |
| Disclaimer (binding) | `docs/explanation/disclaimer.md` |
| Orchestration compliance (binding) | `docs/explanation/orchestration-compliance.md` |
| Requirements | `docs/explanation/requirements.md` |
| Professional sign-off template | `docs/explanation/professional-sign-off-record.md` |
| Compliance self-review | `docs/review/compliance.md` |
| Product vision | `docs/explanation/product-vision.md` |
| Sibling / installable apps | `docs/integrations/sibling-products.md` |
| App catalog | `catalog/apps.json` |
| Workflow | `docs/workflow.md` |
| Commit conventions | `docs/development/commit-conventions.md` |
| Roadmap | `docs/roadmap.md` |
| Current work | private `nene-origin/internal-docs/suite/todo/current.md` (operational logs moved out of this public repo, P3) |

## Collaboration Policy

Follow [`docs/workflow.md`](workflow.md) — inherited from [NENE2](https://github.com/hideyukiMORI/NENE2/blob/main/docs/workflow.md):

1. Create or reuse a GitHub Issue **before** editing.
2. Branch from `main` as `type/issue-number-summary`.
3. Implement, verify, commit with `(#issue)`.
4. Push, open PR with `Closes #number`, merge after checks — **do not push directly to `main`**.

- Use one branch and one PR per focused work unit.
- Keep `docs/milestones/`, `docs/roadmap.md`, and the private current TODO (`nene-origin/internal-docs/suite/todo/current.md`) updated when direction changes.
- Explain intent, impact, verification, and remaining risk in PRs.
- Prefer documentation that helps the next developer or AI agent decide what to do without rereading chat history.

## Secrets

Do not commit passwords, tokens, private URLs, production credentials, or local `.env` files.
Commit only non-secret examples such as `.env.example` when needed.

Sensitive keys for this product include:

- Suite JWT signing secrets
- MySQL root or app credentials generated during install
- Service tokens injected into sibling app environments
- Release ZIP download credentials if private artifacts are used

## Engineering Theme

NeNe Suite should stay small, explicit, and AI-readable:

- Orchestration only — no product domain logic in this repo
- Catalog-driven installs with documented dependency order
- Environment contract for suite mode (`NENE_SUITE_*`) shared across siblings
- Standalone install path must remain valid for every catalog entry
- English repository documentation

## Disclaimer and compliance

NeNe Suite is **setup orchestration only**. Contributors must not add language that
guarantees business outcomes, legal compliance, or professional advice. See
[`docs/explanation/disclaimer.md`](explanation/disclaimer.md).

Installer and catalog changes **must** follow
[`docs/explanation/orchestration-compliance.md`](explanation/orchestration-compliance.md),
[`docs/explanation/terminology.md`](explanation/terminology.md), and
[`docs/review/compliance.md`](review/compliance.md).

## Sibling Products

Domain applications live in their own repositories. Suite references them through
`catalog/apps.json` and release artifacts — it does not fork or embed their source.

# Workflow

NeNe Suite uses GitHub Issues for work tracking and local Markdown for project memory.
This workflow inherits [NENE2 `docs/workflow.md`](https://github.com/hideyukiMORI/NENE2/blob/main/docs/workflow.md) with the substitutions below.

## Standard Flow

1. Create or reuse a focused GitHub Issue.
2. Confirm context in `docs/roadmap.md`, `docs/milestones/`, and `docs/todo/current.md`.
3. Create a branch from `main` named like `type/issue-number-summary`.
4. Implement the smallest useful change.
5. Update docs, roadmap, milestone, or TODO files when the decision or state changes.
6. Review the relevant self-review checklist in `docs/review/` when applicable.
7. Run the narrowest meaningful verification available.
8. Commit with Conventional Commits and include the Issue number.
9. Push the branch and create a PR linked to the Issue.
10. Merge after review and checks.
11. Return local `main` to the merged, clean state.

## Branch Names

Use Conventional Commit style as the prefix:

- `docs/1-governance-foundation`
- `feat/4-docker-compose-installer-mvp`
- `feat/5-suite-env-contract`
- `fix/12-catalog-dependency-order`

## PR Requirements

Every PR should include:

- purpose
- change summary
- verification results
- self-review checklist used, when applicable
- related Issue, preferably `Closes #number`
- remaining risks or follow-up work

Do not commit directly to `main`.

## Continuous Integration

`.github/workflows/ci.yml` runs on every PR and `main` push. It executes the same
self-contained guards available locally — no CI-only checks:

- `tools/check-terminology.sh` (terminology guard, ADR 0006)
- `tools/validate-catalog.sh` (catalog schema + dependency DAG)
- `npx @redocly/cli@2 lint docs/openapi/openapi.yaml` (OpenAPI 3.1 contract)
- `frontend`: `npm ci` + `npm run check` (type-check + Vitest)

Run these locally before opening a PR. `composer openapi` and docs link checking
join CI when their prerequisites land (backend scaffold; link-check tool).

## Operations

- `docs/ops/staging-deploy.md`: how the ConoHa VPS runs staging/demo (shared
  Caddy entry point, `edge` network, `compose.staging.yaml` override).

## Local Project Memory

- `docs/roadmap.md`: long-lived direction and phases
- `docs/milestones/`: medium-sized goals and acceptance criteria
- `docs/todo/current.md`: current task board and handoff notes
- `docs/adr/`: major architecture decisions
- `catalog/apps.json`: installable sibling products and dependency graph

Do not leave important decisions only in chat. If it changes how the project should be built, record it in `docs/`.

Use ADRs for decisions that affect architecture, installer contracts, dependency choices, or long-term maintenance. See `docs/development/adr.md`.

## Daily reports and documentation freshness

**Binding.** A daily report (`docs/daily/YYYY-MM-DD.md` — location/format per the fleet-wide
convention `_work/daily-report-convention.md`) is **not complete** until the same
change **raises the freshness of the living status docs** to match the state the report describes.
This is mandatory, not optional.

When you write or update a daily report you **MUST**:

1. Update `docs/todo/current.md` — status line/date, gate counts (PHPUnit / vitest), and a dated
   entry for what shipped.
2. Reconcile `README.md` "Repository status", `docs/roadmap.md`, and the relevant
   `docs/milestones/` file with reality — ADR ranges and status, phase / ✅ markers, dates.
3. Fix any doc whose stated **status, date, gate counts, or ADR status** no longer matches `main`.
4. Bump `Last updated:` **only on docs you actually changed** — never date-stamp an unchanged doc
   (freshness means accuracy, not cosmetic dates).

A daily report that leaves stale status docs behind is an **incomplete change**. If a given doc needed
no edit, say so in the PR rather than silently skipping the freshness check.

## AI Agent Responsibilities

AI agents should manage the normal lifecycle when asked to complete work:

- create or reuse the Issue
- create the Issue branch
- read `AGENTS.md` and relevant docs before editing
- edit only relevant files
- review relevant self-review checklists
- verify the change
- commit, push, open PR, merge, and sync `main`
- update local docs that describe project state

If a user explicitly says investigation only, no commit, no PR, or another narrower scope, follow that instruction.

## Initial Issues Backlog

Phase 0 bootstrap Issues are tracked in `docs/todo/current.md`. After governance lands, use GitHub Issues for all work — no direct `main` commits.

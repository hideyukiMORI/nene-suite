# Scope Contract (binding)

This document defines what NeNe Suite **is** and **is not**. Implementation and PR review must respect it.

## GOAL

Provide a **selective multi-app installer and apex shell** for NeNe sibling products while preserving standalone installs and separate databases.

## DO

- Maintain `catalog/apps.json` with id, dependency graph, install status, and env keys.
- Orchestrate install, migrate, and initial admin/org provisioning for **selected** catalog entries.
- Generate and distribute suite environment variables (`NENE_SUITE_*`, shared org UUID, JWT issuer config).
- Provide apex login / app launcher UI (thin shell — not product domain UI).
- Support **add app to existing suite** after initial install.
- Document Tier B (Docker Compose) before Tier A web wizard.
- Open Issues in sibling repos when federation requires product-side changes.

## DON'T

- Add billing, reconciliation, CMS, archive, or CSV domain logic in this repo.
- Merge sibling repositories or commit their full source trees.
- Use a shared application database across products.
- Break standalone install paths in sibling products.
- Store production secrets in git.
- Require suite mode for any product to function in isolation.

## Dual mode

| Mode | Trigger | Behavior |
| --- | --- | --- |
| **Standalone** | Product installed directly; `NENE_SUITE_MODE` unset or `0` | Product web installer owns full bootstrap |
| **Suite** | Installed via nene-suite; `NENE_SUITE_MODE=1` | Suite prefills env; apex SSO; org `external_id` set |

Last updated: 2026-05-29

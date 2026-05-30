# Self-Review Policy

Before opening or updating a PR, run the checklists that match your change type.
Name the checklists in the PR body (example: `Self-review: backend-api, terminology`).

## Checklist index

| Change type | Checklist |
| --- | --- |
| Any docs / catalog / terminology | [`../review/terminology.md`](../review/terminology.md) |
| Compliance, installer, audit, manifest | [`../review/compliance.md`](../review/compliance.md) |
| PHP API, use cases, repositories, installer runtime | [`../review/backend-api.md`](../review/backend-api.md) |
| React apex / wizard UI | [`../review/frontend.md`](../review/frontend.md) |
| i18n message catalogs | [`../development/i18n.md`](../development/i18n.md) |
| JSON Schema, catalog structure | [`../review/schema.md`](../review/schema.md) |
| OpenAPI paths and contracts | [`../review/openapi-contract.md`](../review/openapi-contract.md) |
| Governance / ADR only | [`../review/docs-policy.md`](../review/docs-policy.md) |

## Universal gates (every implementation PR)

- [ ] GitHub Issue linked; branch name `type/issue-number-summary`
- [ ] [`coding-standards.md`](./coding-standards.md) and [`inheritance-from-nene2.md`](../inheritance-from-nene2.md) respected
- [ ] [`terminology.md`](../explanation/terminology.md) — exact spellings; `bash tools/check-terminology.sh` passes
- [ ] No secrets in diff; no sibling domain logic
- [ ] ADR updated or cited when architecture changes

## Quality commands (Phase 1+ scaffold)

```bash
composer check
npm run check --prefix frontend
bash tools/check-terminology.sh
git diff --check
```

Doc-only PRs until scaffold: terminology script + link review + applicable checklist above.

Last updated: 2026-05-29

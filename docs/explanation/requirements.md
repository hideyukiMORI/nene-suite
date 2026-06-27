# Requirements

Functional requirements for NeNe Suite. MVP maps to **Phase 1** (Tier B installer)
unless noted.

See also: [`product-vision.md`](./product-vision.md),
[`terminology.md`](./terminology.md) (binding spellings),
[`orchestration-compliance.md`](./orchestration-compliance.md) (binding),
[`scope-contract.md`](./scope-contract.md).

---

## 1. Installer capabilities (Phase 1)

| ID | Requirement | Phase |
| --- | --- | --- |
| R-01 | Operator selects subset of catalog apps; dependency order enforced | 1 |
| R-02 | Separate MySQL database provisioned per selected app | 1 |
| R-03 | Suite writes `NENE_SUITE_*` env per ADR 0004 | 1 |
| R-04 | Operator must accept disclaimer before install completes | 1 |
| R-05 | Install manifest written (no secrets) per orchestration-compliance §6 | 1 |
| R-06 | Apex shell lists installed apps with SSOT labels where applicable | 1 |
| R-07 | Add app to existing suite (incremental install) | 2 |
| R-08 | Append-only `suite_audit_events` with `before_json` / `after_json` for every mutating orchestration action per [`audit-trail.md`](./audit-trail.md) | 1 |
| R-09 | Suite control database (`nene_suite`) separate from sibling app databases | 1 |

---

## 2. Orchestration compliance (binding)

> **Non-negotiable engineering rules.** Governed by
> [`orchestration-compliance.md`](./orchestration-compliance.md). A 税理士 /
> 公認会計士 / 弁護士 reviewing the **suite boundary** must find zero deviations
> from SSOT, DB separation, and federation rules. Any departure requires an ADR
> recording the decision; professional sign-off is advisory and consolidated into
> the recommended pre-release review (orchestration-compliance §9).

Key MUST items for professionals:

- Invoice remains billing SSOT; Clear reconciliation evidence stays in Clear DB.
- No cross-database domain writes during install.
- `organizations.external_id` is IT federation — not tax registration or legal merge.
- Installer language must not imply statutory compliance or unified books.

---

## 3. Dual deployment

| Mode | Trigger | Installer |
| --- | --- | --- |
| Standalone | Product installed directly | Sibling product web installer only |
| Suite | `NENE_SUITE_MODE=1` | NeNe Suite orchestrator |

Standalone path **MUST** remain functional when suite is not used (scope-contract).

---

## 4. Catalog

Authoritative app list: [`catalog/apps.json`](../../catalog/apps.json).
Each entry declares `requires`, `provides`, and database env prefix.

---

## 5. Professional review (advisory)

Recommended (not a binding gate; amended 2026-06-27) — consolidated into a single pass
before a public product release rather than per change:

- Orchestration compliance review (税理士 / 公認会計士) — SSOT + DB + federation
- Legal review (弁護士) — disclaimer and installer copy
- Template: [`professional-sign-off-record.md`](./professional-sign-off-record.md)

The 2026-05-31 sign-offs are on record (orchestration-compliance §9 / ADR 0003 /
ADR 0005). The repository is public; there is no separate private→public gate.

Last updated: 2026-06-27

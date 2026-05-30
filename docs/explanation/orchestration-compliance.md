# Orchestration Compliance — Binding Rules

**Status: binding (non-negotiable).** This document is the source of truth for how
NeNe Suite orchestrates sibling NeNe products so that **tax accountants (税理士),
certified public accountants (公認会計士), lawyers (弁護士), and other licensed
professionals (士業)** can review the **installer boundary** without finding silent
deviations from documented practice.

NeNe Suite does **not** perform billing, reconciliation, or document retention
itself. Its compliance duty is to **preserve each sibling product's system-of-record
boundaries** and to **avoid implying unified legal or accounting truth** where
only technical federation exists.

This is **not legal advice**. It is engineering's binding interpretation of
obligations that apply when a Japan SMB deploys multiple NeNe apps through one
installer. Where interpretation is unclear, **stop and consult a licensed 税理士 /
公認会計士 / 弁護士** — record the resolution in an ADR and update this document.

See also: [`scope-contract.md`](./scope-contract.md), [`terminology.md`](./terminology.md), [`disclaimer.md`](./disclaimer.md),
[`suite-environment-contract.md`](./suite-environment-contract.md),
[`../integrations/sibling-products.md`](../integrations/sibling-products.md),
[`../review/compliance.md`](../review/compliance.md),
[nene-invoice `accounting-compliance.md`](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/explanation/accounting-compliance.md).

---

## 0. Governing principles

1. **Boundary preservation is non-negotiable.** If suite convenience conflicts
   with a sibling SSOT rule (Invoice figures, Clear evidence custody, Vault
   retention), **the sibling rule wins** — suite must not blur it.
2. **No silent deviation.** Any departure from this document requires an **ADR**
   and **explicit professional sign-off** recorded in that ADR (see §9). Code
   may not merge a deviation without it.
3. **Engineering is not the legal authority.** When a requirement touches tax,
   accounting, privacy, or collection law, **stop and consult 士業** — do not
   guess. Record the resolved interpretation here or in the relevant sibling doc.
4. **Suite success is technical, not statutory.** Completing the installer
   **MUST NOT** be presented as tax, audit, or legal readiness.
5. **Single installer manifest.** Every suite install **MUST** produce one
   tamper-evident install manifest (§6) so professionals can see what was
   configured without inferring from scattered `.env` files alone.
6. **Append-only orchestration audit.** Every mutating suite operator action
   **MUST** record before/after sanitized state in the suite control database
   (§6, [`audit-trail.md`](./audit-trail.md), ADR 0007) — not only the final
   manifest snapshot.

---

## 1. Statutory and professional context (installed apps)

NeNe Suite **does not** implement the rules below. It **MUST NOT** break the
conditions under which sibling products implement them.

| Area | Typical sibling owner | Suite's duty |
| --- | --- | --- |
| Qualified invoice (適格請求書) | NeNe Invoice | Do not merge billing DB; do not recalculate tax in suite |
| Consumption tax figures | NeNe Invoice | No cross-app money fields in suite config |
| Payment / outstanding balance (帳簿) | NeNe Invoice | Clear writes only via Invoice `/api/*` contract — suite wires env, not logic |
| Bank evidence & reconciliation links (証憑) | NeNe Clear | Separate DB; suite does not copy invoice rows into Clear |
| Received-document retention (電子帳簿保存法) | NeNe Vault | Separate DB; suite does not alter retention policy |
| Personal data (個人情報保護法) | Each app | Suite logs must not store passwords, tokens, or PII from bootstrap forms |

This table states *what professionals should expect at the boundary*; it is not
legal advice.

---

## 2. System of record (SSOT) — MUST preserve

Professionals reviewing a suite deployment **MUST** be able to identify which
product owns which class of truth. NeNe Suite **MUST** document and preserve:

| Domain truth | System of record | Suite MUST NOT |
| --- | --- | --- |
| Quote / invoice figures, tax, numbering, issued PDF copy | **NeNe Invoice** | Create billing rows in another DB; imply unified ledger |
| Payment records on invoices | **NeNe Invoice** (Clear writes via contract) | Bypass Invoice API during install or runtime wiring |
| Bank CSV lines, match proposals, dunning send log | **NeNe Clear** | Store invoice totals as Clear's authoritative copy |
| Received vendor documents & search metadata | **NeNe Vault** | Mix vault blobs into Invoice DB |
| Normalized bank transactions (StandardTransaction) | **NeNe Profile** output | Treat Profile export as Invoice SSOT |
| CMS entities / schema | **NeNe Records** | Share Records tables with Invoice |

Reference: [nene-invoice sibling integration — Clear upstream](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/integrations/sibling-products.md),
[nene-clear Invoice upstream contract](https://github.com/hideyukiMORI/nene-clear/blob/main/docs/integrations/invoice-upstream-contract.md).

When catalog `requires` declares a dependency (Clear → Invoice), the installer
**MUST** install upstream first and **MUST** surface the SSOT relationship in the
install summary shown to the operator.

---

## 3. Database separation — MUST

- **One MySQL database (or schema) per catalog app** — no shared application tables.
- Suite **MUST NOT** run cross-database INSERT/UPDATE for domain data during install.
- Suite **MUST NOT** introduce a "suite warehouse" DB for business figures.
- SQLite is for sibling product tests only; production suite installs target MySQL
  per sibling Tier A/B docs.

Violating §3 is a **P0 compliance defect**.

---

## 4. Organization federation (`external_id`) — MUST

`NENE_SUITE_ORG_EXTERNAL_ID` maps to `organizations.external_id` in each app
(see ADR 0004). Professionals **MUST** interpret it as follows:

| Statement | True / False |
| --- | --- |
| IT identifier for SSO and env wiring across apps | **True** |
| Proof that legal entities are merged for tax purposes | **False** |
| Substitute for Invoice issuer registration number (`T`+13) | **False** |
| Proof that books are consolidated | **False** |

Suite **MUST NOT** copy Invoice `company_settings.registration_number` into other
apps automatically. Suite **MUST NOT** label `external_id` as "法人番号" or
"適格請求書登録番号" in UI or docs.

---

## 5. HTTP integration wiring — MUST

- Cross-app integration is **HTTP only** (ADR 0002 in each sibling).
- Suite **MAY** generate service tokens and base URLs during install.
- Suite **MUST NOT** alter sibling OpenAPI contracts or payment write semantics.
- Clear → Invoice writes **MUST** remain scoped to
  `read:invoices` + `write:payments` (or future documented scopes) — suite
  **MUST NOT** mint broader scopes by default.
- Enabling a downstream write integration **MUST** require explicit operator
  confirmation in the installer (not pre-checked hidden default).

---

## 6. Install manifest and audit trail — MUST (Phase 1+)

### 6.1 Install manifest (snapshot)

Each completed install **MUST** write a manifest file (path documented in
installer ADR) including at minimum:

- `suite_id`, `installed_at`, catalog app versions
- `org_external_id`, org display name (no secrets)
- per-app database name and public URL
- list of enabled HTTP integrations (e.g. Clear → Invoice)
- installer operator acknowledgment timestamp (disclaimer accepted)

Manifest **MUST NOT** contain: passwords, JWT secrets, service tokens, or full
`.env` dumps. Operators store secrets separately.

### 6.2 Orchestration audit trail (history)

Every **mutating** suite operator action **MUST** append one row to
`suite_audit_events` in the **`nene_suite` control database** with
**`before_json` and `after_json`** sanitized snapshots, per binding spec
[`audit-trail.md`](./audit-trail.md) and [ADR 0007](../adr/0007-suite-audit-trail-before-after.md).

Minimum Phase 1 coverage:

- install session lifecycle (started / completed / failed)
- app selection changes and disclaimer acceptance
- non-secret env / URL wiring written by the installer
- per-app database provisioning (names only)
- integration enable/disable (e.g. Clear → Invoice)
- manifest create/update

Audit rows **MUST NOT** contain secrets (same redaction rules as manifest).
Audit storage is **append-only** — no updates or deletes to historical rows.

The manifest is a **point-in-time snapshot**; the audit trail is the **full
chronological record** professionals use to see *what changed when* during and
after install.

---

## 7. Installer and operator-facing language — MUST / MUST NOT

### MUST

- State that each app retains its own compliance docs and SSOT.
- Link to [`disclaimer.md`](./disclaimer.md) before install completes.
- Use "helps install / orchestrates configuration / separate databases".

### MUST NOT

- "Compliant out of the box", "audit-ready", "税理士不要", "自動で法令対応".
- Imply that unified login equals unified accounting books.
- Imply suite endorsement of sibling tax calculations.
- Display competitor product names in repo docs (portfolio ADR 0013 pattern in Vault).

Copy templates: [`installer-disclaimer-copy.md`](./installer-disclaimer-copy.md).

---

## 8. Professional roles — who owns what

| Role | Responsibility |
| --- | --- |
| **Operator / 事業者** | Business use, configuration inside each app, backups, access control, engaging 士業 |
| **NeNe Suite (software)** | Documented install steps, env contract, SSOT preservation per this doc |
| **Sibling product (software)** | Domain rules per that product's compliance docs (e.g. Invoice `accounting-compliance.md`) |
| **税理士 / 公認会計士** | Tax and accounting interpretation, sign-off on binding docs and material ADRs |
| **弁護士** | Privacy, terms, collection law, disclaimer and liability wording |
| **Suite authors** | Engineering accuracy only — not professional advisory duty |

---

## 9. Professional sign-off and change control

### Before installer MVP ships to external operators

| Review | Reviewer | Record |
| --- | --- | --- |
| SSOT matrix (§2) and DB separation (§3) | **税理士** or **公認会計士** | [`professional-sign-off-record.md`](./professional-sign-off-record.md) |
| Disclaimer + installer copy | **弁護士** (Japan law) | Same record template |
| Federation semantics (§4) | **税理士** recommended | Same record template |

Until recorded, treat Phase 1 installer as **internal / engineering preview**.

### On every change touching install flow, catalog, env contract, or SSOT docs

1. Review against this document and [`../review/compliance.md`](../review/compliance.md).
2. State compliance impact in the PR.
3. If deviating from any rule, carry an ADR with professional sign-off (§0.2).

If unsure whether a change has compliance impact, **assume it does**.

---

## 10. How sibling compliance docs relate

| Document | Applies to |
| --- | --- |
| This file | NeNe Suite installer/orchestrator only |
| [Invoice `accounting-compliance.md`](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/explanation/accounting-compliance.md) | Billing, tax, qualified invoice |
| [Clear `payment-reconciliation-dunning-compliance.md`](https://github.com/hideyukiMORI/nene-clear/blob/main/docs/explanation/payment-reconciliation-dunning-compliance.md) | Reconciliation & dunning |
| [Vault `received-document-compliance.md`](https://github.com/hideyukiMORI/nene-vault/blob/main/docs/explanation/received-document-compliance.md) | Received-document archive |

Installing via suite **does not** reduce the review effort inside each installed app.

---

Last updated: 2026-05-29

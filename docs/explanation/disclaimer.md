# Disclaimer (binding)

**Status: binding.** NeNe Suite operators, contributors, and AI agents must treat
this document as non-negotiable product posture. Changing it requires ADR 0003 or
a superseding ADR plus professional review when legal exposure changes.

**Positive MUST rules** for SSOT, database separation, and federation are in
[`orchestration-compliance.md`](./orchestration-compliance.md) (binding — ADR 0005).
This disclaimer states limits of liability and non-warranty; orchestration-compliance
states what the installer **must** preserve for professional review.

> **This is standard open-source software disclaimer language — not legal advice.**
> Operators with compliance obligations should obtain review from qualified
> professionals (tax accountant, certified public accountant, lawyer) before
> relying on any installed application for regulated business activity.

---

## What NeNe Suite is

NeNe Suite is **installation and environment orchestration software**. It helps
operators:

- select sibling NeNe products to deploy on a host;
- create separate databases and write initial configuration;
- enable suite mode (`NENE_SUITE_MODE=1`) and shared org identifiers;
- expose an apex login shell and navigation between installed apps.

That is **technical setup assistance only**.

---

## What NeNe Suite is not

NeNe Suite is **not**:

| Category | NeNe Suite does not… |
| --- | --- |
| **Business outcome** | guarantee billing accuracy, collection results, cash flow, payroll, inventory, or any KPI |
| **Legal compliance** | certify compliance with 電子帳簿保存法, インボイス制度, 個人情報保護法, labor law, industry regulations, or contract obligations |
| **Professional services** | provide tax, accounting, legal, audit, or consulting advice |
| **Product behavior** | warrant the correctness, completeness, or fitness of sibling applications (Invoice, Clear, Records, Vault, Profile, etc.) |
| **Operational safety** | guarantee uptime, backup success, disaster recovery, or security of the operator's environment |
| **Data integrity** | guarantee that migrated, copied, or linked data across apps is business-correct |

**Successful installation does not mean the operator's business processes are lawful,
complete, or suitable for audit without independent verification.**

---

## Operator responsibilities

The **operator** (deployer, tenant admin, or business owner) alone is responsible for:

1. **Business decisions** — how installed apps are used, who may access them, and what workflows are approved.
2. **Regulatory compliance** — confirming that configuration and usage meet applicable law, contracts, and internal policies.
3. **Professional review** — engaging 税理士 / 公認会計士 / 弁護士 or equivalent when tax, accounting, or legal questions arise.
4. **Environment security** — host hardening, TLS, backups, access control, secret rotation, and monitoring.
5. **Sibling product configuration** — API tokens, retention settings, invoice numbering, dunning content, and export formats in each app.
6. **Data accuracy** — verifying imports, integrations, and cross-app references before relying on them operationally.

NeNe Suite authors and contributors **do not** assume fiduciary, agency, or advisory
duty to operators by providing this software.

---

## Relationship to sibling products

Each installed application is a **separate product** with its own repository,
documentation, compliance posture, and disclaimers. NeNe Suite:

- does **not** merge or subsume those products;
- does **not** vouch for their domain logic;
- does **not** transfer or extend any compliance claim from one app to another.

If Invoice states a compliance *design goal*, that claim applies to Invoice — not
to NeNe Suite because Invoice was installed through the suite installer.

---

## Software warranty

NeNe Suite is provided under the [MIT License](../../LICENSE):

- **AS IS**, without warranty of any kind, express or implied.
- including but not limited to warranties of **merchantability**, **fitness for a
  particular purpose**, and **non-infringement**.

To the maximum extent permitted by applicable law, authors and copyright holders
are **not liable** for any claim, damages, or other liability arising from use of
NeNe Suite or from business reliance on environments it helped configure — whether
in contract, tort, or otherwise.

---

## Installer and documentation language

Future installer UI, README excerpts, and operator guides **must** include or link
to this disclaimer. Short copy: [`installer-disclaimer-copy.md`](./installer-disclaimer-copy.md).

Do not use marketing language that implies:

- "compliant out of the box";
- "audit-ready without review";
- "guaranteed correct" billing, tax, or legal outcomes;
- "certified" or "approved" by any regulator or professional body.

Permitted: "helps install", "orchestrates configuration", "separate databases",
"HTTP integration between sibling apps".

---

## AI agents and contributors

When implementing installer UI, docs, or examples:

- surface this disclaimer before install completion;
- never encode business guarantees in code comments or user-visible strings;
- route compliance questions to sibling product docs and qualified professionals.

---

## Review status

| Field | Value |
| --- | --- |
| **Document status** | Engineering draft (binding within this private repo) |
| **Public release** | Partially unblocked — tax/accounting sign-off completed; legal (弁護士) sign-off pending |
| **Legal review** | Not yet performed — required before external release |
| **Tax/accounting review** | ✅ Completed 2026-05-31 — 辻村総合会計事務所（公認会計士・税理士）, scope: orchestration-compliance §2–§5, ADR 0005. Record: [`sign-off-tax-accounting-2026-05-31.md`](./sign-off-tax-accounting-2026-05-31.md) |
| **Sign-off template** | [`professional-sign-off-record.md`](./professional-sign-off-record.md) |

Update this table when reviews complete (via PR linked to the gate checklist).

---

Last updated: 2026-05-29

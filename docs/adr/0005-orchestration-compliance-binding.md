# ADR 0005: Orchestration Compliance Is Binding

## Status

accepted (amended 2026-06-27 — professional sign-off downgraded from a binding release
gate to **advisory**, consolidated before public release; the §2–§7 engineering MUST
rules in `orchestration-compliance.md` remain binding.)

## Context

NeNe Invoice uses [nene-invoice `accounting-compliance.md`](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/explanation/accounting-compliance.md)
so finance professionals can review billing logic with **zero silent deviations**.
NeNe Clear mirrors that pattern for reconciliation.

NeNe Suite's domain is **installation orchestration**, not accounting. However,
士業 reviewing a "unified NeNe install" care most about **boundary mistakes**:
merged databases, implied unified books, or installer language that suggests
statutory compliance without sibling product review.

A disclaimer alone (ADR 0003) states what we do not warrant. Professionals also
need **positive MUST rules** for SSOT preservation, DB separation, federation
semantics, and sign-off gates — the same structural pattern as Invoice.

## Decision

1. **`docs/explanation/orchestration-compliance.md` is binding** (non-negotiable
   for installer-related work), alongside `scope-contract.md` and `disclaimer.md`.

2. **Compliance conflicts resolve in favor of sibling SSOT** — suite convenience
   never overrides Invoice/Clear/Vault domain boundaries.

3. **Professional sign-off is advisory, not a binding release gate** (amended
   2026-06-27). A consolidated review is **recommended before a public product
   release** rather than per change:
   - 税理士 or 公認会計士: §2 SSOT matrix, §3 DB separation, §4 federation
   - 弁護士: disclaimer and installer operator copy
   Record using [`professional-sign-off-record.md`](../explanation/professional-sign-off-record.md).
   The 2026-05-31 sign-offs are on record (`orchestration-compliance.md` §9). The
   binding boundary rules (§2 SSOT, §3 DB separation, §4 federation) still apply to
   every change regardless of review timing.

4. **Self-review**: [`docs/review/compliance.md`](../review/compliance.md) is
   mandatory for PRs touching install flow, catalog, env contract, or compliance docs.

5. **Sibling compliance docs are authoritative for domain logic.** Suite docs
   reference [nene-invoice accounting-compliance](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/explanation/accounting-compliance.md)
   and Clear/Vault equivalents — they are not replaced or weakened by suite install.

## Consequences

**Benefits.**

- 士業 can review suite the same way they review Invoice — binding rules + checklist + ADRs.
- Operators see explicit SSOT and role separation.

**Costs.**

- Professional review is advised before public release, but does **not** block
  development or merges (amended 2026-06-27; the 2026-05-31 sign-offs are on record).
- PRs touching orchestration require compliance self-review.

## Related

- Issue: `#8`
- ADR 0003 (disclaimer), ADR 0004 (env contract)
- nene-invoice: `docs/explanation/accounting-compliance.md`
- Supersedes: none
- Superseded by: none

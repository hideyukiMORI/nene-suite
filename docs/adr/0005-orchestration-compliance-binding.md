# ADR 0005: Orchestration Compliance Is Binding

## Status

accepted

## Context

NeNe Invoice uses [`accounting-compliance.md`](../explanation/accounting-compliance.md)
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

3. **Professional sign-off is required** before external installer MVP release:
   - 税理士 or 公認会計士: §2 SSOT matrix, §3 DB separation, §4 federation
   - 弁護士: disclaimer and installer operator copy
   Record using [`professional-sign-off-record.md`](../explanation/professional-sign-off-record.md).

4. **Self-review**: [`docs/review/compliance.md`](../review/compliance.md) is
   mandatory for PRs touching install flow, catalog, env contract, or compliance docs.

5. **Sibling compliance docs are authoritative for domain logic.** Suite docs
   reference [nene-invoice accounting-compliance](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/explanation/accounting-compliance.md)
   and Clear/Vault equivalents — they are not replaced or weakened by suite install.

## Consequences

**Benefits.**

- 士業 can review suite the same way they review Invoice — binding rules + checklist + ADR gate.
- Operators see explicit SSOT and role separation.

**Costs.**

- Installer MVP blocked until sign-off records exist.
- PRs touching orchestration require compliance self-review.

## Related

- Issue: `#8`
- ADR 0003 (disclaimer), ADR 0004 (env contract)
- nene-invoice: `docs/explanation/accounting-compliance.md`
- Supersedes: none
- Superseded by: none

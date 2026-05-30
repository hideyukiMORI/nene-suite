# ADR 0006: Terminology Registry Is Binding

## Status

accepted

## Context

NeNe Invoice maintains [`terminology.md`](https://github.com/hideyukiMORI/nene-invoice/blob/main/docs/explanation/terminology.md)
as the single source of truth for identifiers — typos and drift block merge.

NeNe Suite introduces many cross-cutting identifiers (`NENE_SUITE_*`, catalog
`id` values, SSOT labels, JWT claims). Without one registry, installer docs,
catalog JSON, and future UI copy will diverge — especially across AI-assisted edits.

## Decision

1. **`docs/explanation/terminology.md` is the only authoritative spelling list**
   for NeNe Suite terms in this repository.

2. **Exact match is mandatory.** PRs that introduce or use unregistered identifiers,
   or use forbidden variants listed in §11, do not merge.

3. **Same-PR registration.** New identifiers must be added to `terminology.md`
   before or with their first use.

4. **Self-review:** [`docs/review/terminology.md`](../review/terminology.md) is
   required for docs, catalog, env contract, and installer copy changes.

5. **Sibling terms.** When referencing Invoice/Clear/Vault identifiers, link to
   sibling terminology or compliance docs — do not duplicate their full registries here.

## Consequences

**Benefits.**

- Prevents typo drift in env vars and catalog tokens.
- Gives 士業 and reviewers one lookup table for Suite-specific words.

**Costs.**

- Extra step on every new identifier.
- Periodic alignment with sibling repos when federation fields change.

## Related

- Issue: `#10`
- Pattern: nene-invoice `terminology.md`
- Supersedes: none
- Superseded by: none

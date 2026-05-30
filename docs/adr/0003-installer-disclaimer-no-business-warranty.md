# ADR 0003: Installer Disclaimer — No Business or Legal Warranty

## Status

accepted

## Context

NeNe Suite orchestrates installation of sibling products that may touch regulated
business activity (billing, reconciliation, document retention, personal data).
Operators might infer that a unified installer implies unified compliance or
business correctness — it does not.

Portfolio products already use "not legal advice" language for domain compliance
docs (Vault, Clear, Invoice). Suite sits **above** those products and must not
imply a stronger warranty than any single app provides.

We need a **binding, repository-wide disclaimer** that:

- limits Suite to technical environment setup;
- rejects business, legal, tax, and compliance guarantees;
- assigns operational and professional review duty to the operator;
- supplies copy for future installer UI.

This ADR is engineering policy documentation — **not legal advice**. Material
changes should be reviewed by qualified counsel when the product ships publicly.

## Decision

1. **`docs/explanation/disclaimer.md` is binding.** PRs that add installer UI,
   operator docs, or marketing language must comply with it.

2. **NeNe Suite warrants only orchestration behavior** (catalog resolution, env
   generation, install steps documented in this repo) — not sibling domain outcomes.

3. **No user-visible text** in this repository may claim business, legal, or
   regulatory guarantees. Use [`installer-disclaimer-copy.md`](../explanation/installer-disclaimer-copy.md)
   for UI strings.

4. **MIT License "AS IS"** remains the software warranty baseline; the disclaimer
   clarifies product scope for operators and reduces misinterpretation — it does not
   replace the license.

5. **Sibling product compliance claims stay in sibling repos.** Suite docs link to
   sibling products but do not adopt their compliance posture.

## Consequences

**Benefits.**

- Clear boundary between "install helper" and "business system guarantee".
- Consistent posture for AI agents authoring installer flows.
- Aligns with portfolio pattern ("not legal advice" in domain repos).

**Costs.**

- Installer UX must include acknowledgment step(s).
- Marketing language for Suite must stay conservative.

**Follow-up.**

- Implement disclaimer checkbox in Tier B / Tier A installer (Issue TBD).
- Cross-link from sibling product suite-integration docs when `NENE_SUITE_MODE` lands.

## Related

- Issue: `#3`
- Binding doc: `docs/explanation/disclaimer.md`
- UI copy: `docs/explanation/installer-disclaimer-copy.md`
- Supersedes: none
- Superseded by: none

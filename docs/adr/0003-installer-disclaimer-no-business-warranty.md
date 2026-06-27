# ADR 0003: Installer Disclaimer — No Business or Legal Warranty

## Status

accepted (amended 2026-06-27 — professional review downgraded from a binding gate to
**advisory**, consolidated before a public product release; see "Professional review
(advisory)" below. The MIT "AS IS" / no-warranty posture is unchanged.)

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

## Professional review (advisory)

> **Amended 2026-06-27 (owner decision).** Professional review is **advisory, not a
> binding merge gate.** Requiring per-change tax/legal sign-off during development stalls
> iteration, so review is **consolidated into a single recommended pass before a public
> product release** (and before materially expanding scope — e.g. hosted SaaS that
> custodies third-party data; see [ADR 0015](0015-suite-hosted-multi-tenant-mode.md)).
> This replaces the former binding "review gate" and its private→public trigger — the
> repository has always been public. The 2026-05-31 sign-offs remain on record as the
> last completed review. The **MIT "AS IS" / no-warranty** posture in `disclaimer.md`
> is **not** changed by this amendment; only the *review process* moves to advisory.

Treat `disclaimer.md` and installer copy as **engineering's best-effort posture**,
reviewed by qualified counsel on a **recommended, consolidated** basis — not as a
precondition for each PR.

**Recommended (not required) before a public product release or marketing push:**

| Step | Owner | Record |
| --- | --- | --- |
| Legal review of `disclaimer.md`, `DISCLAIMER.md`, and planned installer / Terms copy | Qualified **lawyer** (日本法に精通) | Date + summary in milestone or ADR amendment PR |
| Tax/accounting overclaim review if docs reference インボイス / 電帳法 / SMB back-office workflows | **税理士** or **公認会計士** | Date + "no overclaim" or required doc edits |
| Engineering sign-off that UI surfaces disclaimer before install completes | Maintainer | Linked Issue / PR |

**Out of scope**

- Proving that installed sibling apps meet law — that remains each product's docs + operator + professionals.
- Replacing operator duty to obtain ongoing advice.

## Related

- Issue: `#3`
- Binding doc: `docs/explanation/disclaimer.md`
- UI copy: `docs/explanation/installer-disclaimer-copy.md`
- Supersedes: none
- Superseded by: none

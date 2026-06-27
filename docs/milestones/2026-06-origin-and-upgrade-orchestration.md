# Milestone: Origin Consumption & Upgrade-Orchestration Prerequisites (2026-06)

## Status

**Origin consumption client — COMPLETE** (epic #230). **O6 upgrade-orchestration prerequisites
①–④ — COMPLETE / accepted** (2026-06-25/26). **Next:** ADR 0019 acceptance (open questions) → O6
orchestrator implementation. Builds on the multi-tenant foundation — see
[`2026-06-multi-tenant-suite.md`](./2026-06-multi-tenant-suite.md).

## Context

NeNe Origin (private, vendor-operated authority) distributes updates, announcements, and house-ads;
every product consumes it. NeNe Suite is **one Origin client among many** plus, in suite mode, an
**aggregator and upgrade orchestrator** — never the authority (ADR 0013, ADR 0017). The apply itself
stays each sibling's own Tier A (ADR 0013 §3/§8); Suite orchestrates only.

## Shipped (merged to `main`)

### Origin consumption client — epic #230 (O0–O5b)

Profiled-TUF read model end to end: outbound HTTP seam + `ext-sodium`; detached-JWS (EdDSA) verify
with **conformance-corpus parity (15/15)**; per-product `gen` watermark (anti-rollback); update +
announcement + house-ad read APIs (operator-auth, disabled-degrade); dashboard / app-detail wiring.
ADR 0017 consumer. PRs #232–#250.

### O6 upgrade-orchestration prerequisites

| # | Prerequisite | Status | PRs / refs |
| --- | --- | --- | --- |
| ① | installed-version tracking — sibling auth-gated `/machine/health` `version` → control-DB cache → update diff (`unknown` → real) | ✅ | #256 (seam), #258 (`X-NENE2-API-Key` probe + machine-key env); NENE2 v1.5.330 / NENE2#1414 |
| ② | catalog version mirror — ADR 0013 §4 read-model on the catalog API (`installedVersion` / `availableVersion`) | ✅ | #259 / #260 |
| ③ | ADR 0013 accepted (update aggregation & dependency-ordered upgrade orchestration) | ✅ | #262 |
| ④ | upgrade-orchestration contract | ✅ (contract) | ADR 0018 (#266 proposed → #268 accepted) → **superseded by ADR 0019** (deployment-driven, #272) |

### Course correction (2026-06-26)

- **ADR 0018 → ADR 0019.** ADR 0018 specified the apply via a sibling **runtime HTTP** endpoint
  (`POST /machine/update`, self-apply); review found a running app cannot redeploy itself. ADR 0019
  makes the apply **deployment-driven**: Suite (Tier B compose owner) recreates the sibling container
  at the new image; the sibling migrates on boot (its own Tier A, ADR 0014). **NENE2#1416 withdrawn.**
- **NENE2 framework-health audit.** NENE2 stays Suite-agnostic (one-directional Suite→NENE2); zero
  code/config coupling. A generic-prose blemish (our issue wording) was fixed on the NENE2 side
  (NENE2#1417/#1418). Principle recorded (`feedback_nene2_suite_agnostic`).

## Key decisions

- **Apply ownership.** The apply is the sibling's own Tier A (boot migration, ADR 0014); Suite
  orchestrates the deployment swap (compose recreate) only and never writes inside a sibling tree
  (ADR 0013 §3/§8, ADR 0019).
- **Installed-version source.** The sibling's auth-gated `/machine/health` `version`
  (`X-NENE2-API-Key`), cached in the control DB — not the static `catalog/apps.json` (values are
  live). Origin unconfigured / version unreported → `unknown` (defensive, no fabricated data).
- **NENE2 is Suite-agnostic.** Only generic framework features are requested from NENE2; the
  framework never names or alludes to Suite.

## Remaining / next

1. **ADR 0019 → accepted** (design gate before implementation). Open questions: deploy-control
   mechanism (Docker socket vs host-side deploy agent — security), image provenance (registry trust
   vs Origin signature), Tier A (release-ZIP) coexistence.
2. **O6 orchestrator (Suite)** — bring sibling services into Suite's compose project; drive
   dependency-ordered image recreate with min-version gating + halt-don't-unwind + audit; apex
   "update all" UI; signed-result caching (ADR 0013 §2).
3. **Cross-repo (not Suite-blocking)** — sibling adoption for live version data: nene-invoice#496 /
   nene-clear#182 / nene-records#586 (upgrade to NENE2 v1.5.330+ and inject `appVersion`).
4. **Production activation is human-gated** — root-key ceremony + `NENE_ORIGIN_URL` /
   `NENE_ORIGIN_TRUST_ANCHOR_PATH`; once configured the placeholders become live data.

## References

- ADRs: **0017** (Origin consumption, accepted), **0013** (update aggregation, accepted),
  **0018** (aggregation contract — superseded), **0019** (Tier B deployment-driven upgrade,
  proposed), **0014** (boot-time migration), **0007** (audit).
- Epics: **#230** (Origin consumption, closed), **#251** (upgrade orchestration).
- Handover: [`../handover/2026-06-26-origin-and-o6-prerequisites.md`](../handover/2026-06-26-origin-and-o6-prerequisites.md).
- Prior milestone: [`2026-06-multi-tenant-suite.md`](./2026-06-multi-tenant-suite.md).

Last updated: 2026-06-26. Still current as of **2026-06-27** — ADR 0019 remains **proposed** (its open questions are the next gate); the O6 deployment-driven orchestrator + apex "update all" UI (epic #251) are the remaining work.

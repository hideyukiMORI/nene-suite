# ADR 0013: Update Aggregation & Dependency-Ordered Upgrade Orchestration

## Status

accepted (2026-06-25)

The consumer decisions (§1–§4, §6–§8) are validated by the landed Origin client, installed-version
tracking, and catalog version mirror (see **Implementation status**). The dependency-ordered upgrade
orchestrator (§3) and the full Suite↔sibling update relay (§5 beyond its auth surface) remain
follow-up under epic #251 — built on top of this accepted contract, not blockers to acceptance.

## Context

The NeNe series gets official updates, announcements, and free-tier house-ads from
**NeNe Origin** — a private, vendor-operated authority. Origin owns the normative
**Origin contract** (manifest/signing/Tier A apply in nene-origin ADR 0001; announcements
& house-ads in nene-origin ADR 0002). Every product, **standalone or suite**, consumes it.

NeNe Suite's question is narrower: *what does the orchestrator add on top?* Per the scope
contract, Suite is a thin orchestrator that must **never be required for a product to
function** and must **not break standalone install paths**. So Suite is **one client of
Origin among many** — plus, in suite mode, an **aggregator and upgrade orchestrator**.

This mirrors the asymmetry already accepted in ADR 0012: the authority lives elsewhere,
Suite **mirrors** and **coordinates** but is **not the source of truth**. Roadmap Phase 4
("upgrade path per catalog app version pin", "health dashboard aggregating sibling
`/health`") is exactly this surface.

## Decision

### 1. Suite consumes the Origin contract; it does not own or redefine it

Suite implements the **client** side of nene-origin ADR 0001 (fetch signed manifest,
verify with embedded public keys, read announcements/ads). Suite **does not** publish
manifests, sign artifacts, host ads, or fork the manifest schema. `NENE_ORIGIN_URL` is
**portfolio-neutral** (consumed identically by standalone siblings) and is **not** a
`NENE_SUITE_*` variable.

Suite owns exactly one contract: the **aggregation contract** (§5) between Suite and the
siblings it manages — never the Origin contract.

### 2. Single egress + fan-out (suite mode only)

In suite mode the apex hub:

- polls Origin **once** (single network egress; better privacy and one boundary to
  audit), caches the signed results, and
- exposes an **aggregated** view ("3 apps have updates", consolidated announcements) in
  the apex shell, and relays per-sibling update availability to each sibling.

In **standalone** mode a sibling polls Origin **directly**. Suite presence is an
optimization, never a requirement (scope contract).

### 3. Dependency-ordered "update all"

Suite already validates a dependency DAG over `catalog/apps.json` (`requires`, via
`tools/validate-catalog.sh`). Suite reuses it to **sequence multi-app updates**:

- Order updates so dependencies upgrade before dependents (e.g. `nene-invoice` before
  `nene-clear`).
- Honor the **min-compatible dependency versions** published in the Origin manifest
  (ADR 0001 §6): Suite **refuses/halts** an update that would leave a dependent below its
  required minimum, and surfaces the conflict instead of applying a breaking set.
- Each sibling performs its **own Tier A atomic apply** (ADR 0001 §5). Suite **orchestrates
  ordering and gating only** — it does not apply updates inside another product's tree.

### 4. Catalog version mirror (mirror, not originate)

The catalog gains per-app version metadata — `installed_version` and `available_version` — as a
**read-model mirror** surfaced on the catalog API (`CatalogApp`; camelCase `installedVersion` /
`availableVersion`), not static values baked into `catalog/apps.json` (the values are live).
`available_version` mirrors the **verified Origin manifest** latest; `installed_version` mirrors the
sibling's own reported version, read from its auth-gated `GET /machine/health` (`version` field,
NENE2 v1.5.330+). Suite reflects these truths and never originates version authority (same
non-authority pattern as the ADR 0012 org-roster mirror). Either field is null when unknown (Origin
unconfigured, app not installed, or version not reported) — no fabricated data.

### 5. Aggregation contract (Suite ↔ sibling) — the only contract Suite owns

The relay between Suite and its managed siblings is **service-token authenticated**
(existing service-token surface). It covers: how Suite reports "update available /
required" to a sibling, and how an operator-initiated "update all" triggers each
sibling's apply in dependency order. The contract is defined here and may be specified as
a Suite OpenAPI surface in follow-up work. Its authentication surface is already exercised by the
installed-version probe (Suite presents the per-app machine API key as `X-NENE2-API-Key` to the
sibling's `/machine/health`); the update-relay and `update all` trigger are follow-up (epic #251).

### 6. Entitlement propagation

In suite mode, the free/paid entitlement (`tier` / `ads_off`) is carried as an **ADR 0012
IdP claim** in the suite federation assertion and propagated to siblings (convenience
toggle for ads-off; not enforcement). Standalone siblings obtain entitlement from Origin
directly (nene-origin ADR 0002 §5).

### 7. Audit

Every operator-initiated update/orchestration action is recorded in the suite audit trail
with before/after snapshots (ADR 0007), e.g. "update all: invoice 1.3.0→1.4.0,
clear 1.1.0→1.2.0".

### 8. Non-goals

- Suite is **not** the update authority; it does not sign, host artifacts, or host ads.
- Suite never breaks the standalone update path; `NENE_ORIGIN_URL` stays portfolio-neutral.
- Suite does not re-implement a sibling's domain update logic.

## Terminology registry impact (same PR — ADR 0006)

- **Register `NENE_ORIGIN_URL`** as a portfolio-neutral (non-`NENE_SUITE_`) variable that
  points at the NeNe Origin authority, consumed by Suite and all siblings alike.
- Catalog version fields (`installed_version` / `available_version`) are **registered** in
  terminology §2.5 (landed with the catalog version mirror, #260).

## Consequences

**Benefits.** Suite adds genuine value (one egress, consolidated UX, safe dependency-ordered
multi-app upgrades) without becoming an authority or coupling standalone installs to it.
Compatibility breakage across apps is prevented by honoring Origin min-versions over the
catalog DAG.

**Costs / follow-up.**
- Implement the Origin client (verify + poll) in Suite.
- Catalog schema + `apps.json` version-mirror fields; dependency-ordered orchestrator;
  service-token aggregation endpoints.
- Apex shell UI for aggregated updates/announcements.

**Risks.** Suite must verify Origin signatures with the same rigor as any client; a lax
aggregator would undermine the supply-chain guarantees of ADR 0001.

## Implementation status (at acceptance, 2026-06-25)

- ✅ **Origin consumption client** — profiled-TUF verification with conformance-corpus parity, the
  `gen` watermark, and the update / announcements / house-ads read APIs (epic #230; ADR 0017 consumer).
- ✅ **Installed-version tracking** — sibling `/machine/health` probe with the per-app machine key,
  feeding the §3 version diff (#255–#258; NENE2 v1.5.330 / NENE2#1414).
- ✅ **Catalog version mirror (§4)** — read-model on the catalog API (#259 / #260).
- ⏳ **Dependency-ordered upgrade orchestration (§3)**, the **Suite↔sibling update relay / "update all"
  (§5 beyond auth)**, and signed-result **caching (§2)** — epic #251. The apply stays in each
  sibling's own Tier A (§3 / nene-origin ADR 0001 §5).

## Related

- Issue: `#98`
- Origin contract (authority, separate private repo): nene-origin **ADR 0001** (update &
  Origin contract), **ADR 0002** (announcements & house-ads).
- ADR 0002 (orchestrator, not monolith), ADR 0007 (audit before/after),
  ADR 0012 (federation — entitlement IdP claim, asymmetric trust model), ADR 0006
  (terminology binding).
- `docs/roadmap.md` Phase 4 (upgrade path per catalog version pin; health dashboard).
- `docs/integrations/sibling-products.md` (service tokens).
- Implementing epic: `#251`; PRs `#256`, `#258`, `#260` (with `#255` / `#257` / `#259`).
- Cross-repo: NENE2 auth-gated `/machine/health` app version — NENE2#1414 (shipped v1.5.330).
- Supersedes: none. Superseded by: none.

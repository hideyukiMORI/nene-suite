# ADR 0019: Tier B Upgrade Orchestration is Deployment-Driven

## Status

accepted (2026-07-02; proposed 2026-06-26 — OQ1/OQ2 resolved below, OQ3 explicitly deferred)

**Supersedes ADR 0018.** ADR 0018 specified the upgrade apply via a sibling **runtime HTTP endpoint**
(`POST /machine/update` — the running app applies its own update). Review found this mis-locates the
apply mechanism: a running web app cannot atomically redeploy and restart itself from within a
request handler, and in Tier B the deployment swap is the orchestrator's job. This ADR replaces that
mechanism with a **deployment-driven** model. The direction is settled; the mechanism details that
were open at proposal time are resolved in the 2026-07-02 amendment below.

## Context

- **ADR 0013 §3/§8 (accepted)** keep the apply with the sibling: "each sibling performs its own
  **Tier A atomic apply**; Suite orchestrates ordering and gating only and does not apply updates
  inside another product's tree."
- **Tier A** is the product's own apply path — its web installer / migrate-bootstrap, reusable by
  Suite (ADR 0002), consuming release ZIPs (roadmap Phase 3).
- **Tier B (current MVP)** is the **Docker Compose orchestrator**: Suite owns the compose project.
- **ADR 0014**: schema/data apply happens as **boot-time migration** on server start
  (`phinx migrate` in the entrypoint) — this is how Suite itself upgrades.

ADR 0018's runtime self-apply endpoint contradicts these: the unit of "update X to version V" in
Tier B is a **new image + container recreate**, after which X migrates itself on boot. That is a
deployment action (Suite's, as compose owner) plus the sibling's own boot migration (its Tier A) —
not an HTTP call the app makes to itself.

## Decision

### 1. The apply is deployment-driven (Tier B)

"Update app X to version V" in suite mode = Suite, as the Tier B Docker Compose orchestrator, pulls
X's new image at version V and **recreates X's container**; X applies its own schema/data **on boot**
(its Tier A — entrypoint migrate, ADR 0014). Suite drives the deployment swap; the sibling applies
its own migrations. Recreating a container with a new image is **deployment, not in-tree mutation** —
Suite still never edits inside X's tree (ADR 0013 §8 holds).

### 2. No sibling runtime self-apply endpoint

There is **no** `POST /machine/update` (or equivalent) on the sibling — a running app does not
redeploy itself over HTTP. NENE2's machine surface for upgrades stays **read-only**:
`GET /machine/health` returns the app version (NENE2#1414, shipped v1.5.330), which Suite uses to
compute the update diff (ADR 0013 §4) and to verify the post-recreate result. NENE2#1416
(`/machine/update`) is withdrawn.

### 3. Suite-side orchestration (retained from ADR 0018 §4)

Operator-initiated **"update all"**:

- **Dependency order** over the catalog `requires` DAG (`tools/validate-catalog.sh`) — dependencies
  before dependents.
- **Min-version gating** — honor the Origin manifest's min-compatible versions (ADR 0013 §3); refuse/
  halt a set that would leave a dependent below its minimum, surfacing the conflict.
- **Drive + verify** — recreate each sibling's container at the Origin-verified target image in order,
  then confirm it is healthy and reports the target version via `/machine/health` before proceeding.
- **Halt, don't unwind** — stop the chain on a failed upgrade; already-upgraded dependencies stay (each
  boot-migrate was the sibling's own atomic step). Re-running resumes from the failed step.
- **Audit** — record each orchestration action before/after (ADR 0007), e.g.
  `update all: invoice 1.3.0→1.4.0, clear 1.1.0→1.2.0`.

### 4. Capability requirement (the real O6 build-out)

Suite needs **deployment control** over sibling containers: the siblings must be part of Suite's
compose project (Tier B), and Suite must drive `compose pull` + recreate per service. The current
`docker-compose.yml` runs only `suite` + `db` (siblings are stubs), so unlocking sibling services and
giving Suite a deploy path (see the resolved questions below) is the substance of the O6 orchestrator.

### 5. Non-goals (retained)

- Suite is **not** the apply authority for a sibling's data; the sibling migrates itself.
- Standalone is unaffected — a sibling self-updates via its own Tier A / ops without Suite.

## Resolved questions (2026-07-02 amendment)

### OQ1 — deployment control is a host-side deploy agent (no Docker socket in Suite)

Suite's container does **not** get the Docker socket. A socket mount is root-equivalent on the
host — a compromised Suite could do anything to any container — which is unacceptable for the
hosted-edition posture and outsized for the actual need (pull + recreate a known service list).

Instead, a **host-side deploy agent** executes the deployment: a script/agent running on the
compose host (the same trust domain that already runs `ops/staging/deploy-staging.sh`) performs
`compose pull` + `up -d` **per service against an explicit allow-list** of sibling services, and
reports the result. Suite requests a deployment through a narrow, explicit, audited seam and then
**verifies the outcome independently** via the sibling's `/machine/health` version (§3). The
concrete request transport (file/queue handshake, local invocation, or an equivalent) is an
implementation detail of the first deploy-control slice, bounded by these invariants:

- the agent only ever acts on the allow-listed compose services, and only `pull` + recreate;
- every request and result is recorded in the suite audit trail (ADR 0007) with before/after;
- the capability is an explicit **opt-in capability flag (default off)** — available to every
  edition, OSS self-host included; absence of the agent (or the flag) degrades to
  "updates visible, apply manual" (disabled-degrade, the same posture as the Origin client).

Trade-off accepted: one more moving part (agent lifecycle on the host) in exchange for a much
smaller blast radius than a socket mount, and continuity with the existing host-side deploy
pattern.

### OQ2 — image provenance is staged: digest pin + post-recreate verify now, Origin-signed later

- **Stage 1 (now).** The catalog pins each app's target release to an **immutable image digest**
  (not a mutable tag). The deploy agent recreates at that digest; Suite then confirms the sibling
  is healthy and reports the target version via `GET /machine/health` (the existing probe,
  ADR 0013 §4) before proceeding down the dependency chain. Trust chain: content-addressed digest
  (registry) plus independent post-apply version verification (sibling).
- **Stage 2 (when Origin production distribution resumes).** The Origin-signed metadata
  (ADR 0017) carries the image digest per version, upgrading the pin from catalog-managed to
  **Origin-verified end to end**. Origin today ships detached-JWS provenance for release ZIPs
  only — it has no OCI-image provenance — so Stage 2 is sequenced behind Origin's own build-out
  and **must not** block the O6 orchestrator (Suite already degrades with Origin disabled).

### OQ3 — Tier A (release-ZIP web installer) coexistence: explicitly deferred

The product family's Tier A installer toolkit is in progress in the sibling repos; how a
non-container Tier A apply path coexists with the deployment-driven model is **deferred until
that toolkit lands** and will be decided then (a new ADR or an amendment here). This deferral
does not gate O6: the Tier B compose path above is self-contained.

## Consequences

**Benefits.** Matches how the stack actually deploys (image + boot-migrate, ADR 0014); keeps the
apply atomic and sibling-owned while making Suite's role honest deployment orchestration; no fragile
runtime self-apply; no new sibling endpoint to build or secure.

**Costs / follow-up.** Suite must gain the host-side deploy agent + seam (OQ1 resolution) and sibling
services must join its compose project. Image provenance starts as a catalog digest pin verified via
`/machine/health` (OQ2 Stage 1); the Origin-signed upgrade (Stage 2) follows Origin's own build-out.

**Risks.** Deployment control is a powerful capability — a compromised Suite could recreate sibling
containers; OQ1's mechanism choice bounds that blast radius. Mitigated by treating it as an explicit,
audited, opt-in capability (default off).

## Related

- **Supersedes: ADR 0018** (Suite↔Sibling Aggregation Contract — runtime self-apply mechanism).
- ADR 0013 §3/§8 (apply is the sibling's; Suite orchestrates), ADR 0014 (boot-time migration),
  ADR 0002 (orchestrator, not monolith; Tier A reuse), ADR 0007 (audit), nene-origin ADR 0001 §5
  (Tier A apply).
- Epic: `#251`. NENE2#1414 (`/machine/health` version) stays; **NENE2#1416 (`/machine/update`)
  withdrawn**.
- `docs/roadmap.md` Phase 4; `docs/integrations/sibling-products.md`.
- Superseded by: none.

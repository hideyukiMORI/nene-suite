# ADR 0018: Suite↔Sibling Aggregation Contract

## Status

accepted (2026-06-25)

The contract shape (§1–§6, decisions A / B1 / C1) is accepted and the four open questions are
resolved below. Implementation is follow-up: NENE2 implements the `/machine/update` endpoints
(cross-repo, like NENE2#1414), and Suite builds the dependency-ordered orchestrator + apex
"update all" UI (O6, epic #251).

## Context

ADR 0013 (accepted) §5 defined the **aggregation contract** — the relay between Suite and the
siblings it manages — at a high level, and deferred the detailed spec to follow-up. This ADR
specifies it. It is the only contract Suite owns (ADR 0013 §1/§5); the Origin contract stays with
NeNe Origin.

Constraints from accepted decisions:

- The **apply** is each sibling's own **Tier A atomic apply** (nene-origin ADR 0001 §5). Suite
  **orchestrates ordering / gating / relay only** and never writes inside another product's tree
  (ADR 0013 §3/§8).
- Suite is **one Origin client among many**; standalone install/update paths must keep working
  **without** Suite (scope contract). Suite presence is an optimization, never a requirement.
- The Suite↔sibling auth surface already exists: NENE2's auth-gated `/machine/*` endpoints validate
  the per-app machine API key as `X-NENE2-API-Key` (NENE2#1414 / Suite #258), reached over the
  internal network. The installed-version probe (`/machine/health`) is the first user of it.

## Decision

### 1. Direction & placement — sibling-exposed `/machine/*`, called by Suite

The aggregation endpoints live on the **sibling** (NENE2 base machine surface), authenticated by
`X-NENE2-API-Key`, called by Suite over the internal network — the same surface as `/machine/health`.
Suite **owns this contract** (defines the shape); NENE2 implements the endpoints (cross-repo); Suite
implements the consuming orchestrator. These endpoints are **not** part of Suite's own
`docs/openapi/openapi.yaml` (Suite consumes them, it does not serve them); they are formalized in
NENE2's OpenAPI when implemented.

### 2. Surface — apply trigger + status only (no availability push)

Two endpoints:

**`POST /machine/update`** — Suite triggers the sibling's own Tier A apply to a target version.

```jsonc
// request
{ "target_version": "1.4.0" }
// 202 Accepted
{ "operation_id": "01J…", "status": "pending", "from_version": "1.3.0", "to_version": "1.4.0" }
```

Idempotent per target: a repeat call while an apply to the same `target_version` is in flight returns
the same `operation_id` (it does not start a second apply). A concurrent `POST` for a *different*
target while an apply is in flight is rejected with `409 Conflict` — one apply per sibling at a time.

**`GET /machine/update/status`** — Suite polls progress of the in-flight (or last) operation.

```jsonc
{ "operation_id": "01J…", "status": "pending|applying|applied|failed",
  "from_version": "1.3.0", "to_version": "1.4.0", "error": null }
```

**"Update available" is not a Suite→sibling push.** Suite already computes per-app update standing
(Origin aggregation × installed version, ADR 0013 §4) and surfaces it in the **apex aggregated view**.
A sibling in suite mode learns availability there; standalone, it reads Origin directly. The contract
therefore carries **no availability relay endpoint** — the "relay" named in ADR 0013 §5 is realized
as the apex view, not a sibling endpoint.

### 3. Apply semantics — async, sibling-atomic, target re-verified

- **Async.** `POST /machine/update` returns immediately with an `operation_id`; the apply runs in the
  sibling and Suite polls status. A Tier A apply can take minutes — a synchronous call would tie up
  the orchestrator and time out.
- **Sibling-atomic + self-rollback.** The sibling performs its own Tier A atomic apply and rolls back
  its own tree on failure (nene-origin ADR 0001 §5). Suite never writes inside the sibling tree.
- **Target re-verified by the sibling.** The sibling verifies `target_version` against Origin itself
  before applying — defense in depth. Suite is **not** the artifact authority; a compromised or buggy
  Suite must not be able to push an unverified version. Suite's role is ordering and gating, not
  vouching for the artifact.

### 4. Orchestration (Suite side — specified here, built in the O6 step)

Operator-initiated **"update all"**:

- **Dependency order.** Sequence updates over the catalog `requires` DAG (`tools/validate-catalog.sh`)
  so dependencies upgrade before dependents (e.g. `nene-invoice` before `nene-clear`).
- **Min-version gating.** Honor the Origin manifest's min-compatible versions (ADR 0013 §3): Suite
  **refuses/halts** a set that would leave a dependent below its required minimum, surfacing the
  conflict instead of applying a breaking set.
- **Drive + poll.** Trigger each sibling's `POST /machine/update` with the Origin-verified
  `target_version` in dependency order, poll `GET /machine/update/status`, and **halt the chain on a
  failed apply** — never start a dependent on a broken dependency. Halting does **not** unwind
  already-applied dependencies (each apply was atomic); re-running "update all" resumes from the
  failed step.
- **Audit.** Record each operator-initiated orchestration action in the suite audit trail with
  before/after (ADR 0007), e.g. `update all: invoice 1.3.0→1.4.0, clear 1.1.0→1.2.0`.

### 5. Auth & transport

`X-NENE2-API-Key` — the per-app machine key already used for `/machine/health`
(`NENE_SUITE_APP_{SNAKE}_MACHINE_KEY` on the Suite side; the sibling's own `NENE2_MACHINE_API_KEY`),
over the internal network. No new credential type.

### 6. Non-goals

- Suite is **not** the apply authority; it does not apply inside a sibling's tree, sign, or host
  artifacts.
- The standalone update path is unaffected — a sibling applies its own updates without Suite.
- This ADR does not implement the endpoints (NENE2) or the orchestrator/apex UI (Suite); those are O6
  follow-up (#251).

## Resolved at acceptance (2026-06-25)

- **OQ1 — status endpoint shape → single in-flight.** `GET /machine/update/status` returns the
  current (or last) operation with its `operation_id`. No addressable per-id history endpoint —
  YAGNI for "update all".
- **OQ2 — concurrency → one apply per sibling.** A second `POST /machine/update` for a *different*
  target while an apply is in flight returns `409 Conflict` (a repeat for the *same* in-flight target
  is the idempotent no-op of §2). Suite also serializes the dependency chain.
- **OQ3 — failure policy → halt, don't unwind.** On a mid-chain failure Suite stops the chain and
  reports a partial result (applied set + halted-at); already-applied dependencies stay in place
  (each apply was atomic). Re-running "update all" resumes from the failed step.
- **OQ4 — target selection → explicit + re-verified.** Suite passes an explicit `target_version`
  from its Origin-verified aggregation; the sibling re-verifies that target against Origin before
  applying (§3). Suite never selects an unverified target, and the sibling stays the authority on its
  own artifact.

## Terminology impact (ADR 0006)

The machine-update surface terms are registered when NENE2 implements them: `/machine/update`,
`operation_id`, and the apply `status` values (`pending` / `applying` / `applied` / `failed`). These
are NENE2-side identifiers; Suite registers only what it consumes.

## Consequences

**Benefits.** A minimal, auth-reused contract that lets Suite deliver safe, dependency-ordered
"update all" without becoming an authority or coupling standalone installs to it; the apply stays
atomic and sibling-owned.

**Costs / follow-up.**

- NENE2 implements `POST /machine/update` + `GET /machine/update/status` on the base machine surface
  (cross-repo issue, like NENE2#1414).
- Suite implements the dependency-ordered orchestrator + apex "update all" UI (O6, #251).
- Per-app sibling adoption.

**Risks.** Suite must never become a path to apply an unverified version — mitigated by §3
sibling-side re-verification. A lax orchestrator that ignored min-versions could push breaking sets —
mitigated by §4 gating. Both must hold for the contract to be safe.

## Related

- ADR 0013 (update aggregation & dependency-ordered upgrade orchestration — this ADR specifies §5;
  honors §3/§8).
- nene-origin ADR 0001 §5 (Tier A atomic apply); nene-origin ADR 0002 (announcements & house-ads).
- ADR 0007 (audit before/after), ADR 0012 (federation / entitlement), ADR 0017 (Origin consumption).
- Epic: `#251`. Auth surface: NENE2#1414 (shipped v1.5.330) / Suite `#258`.
- `docs/integrations/sibling-products.md` (service tokens); `docs/roadmap.md` Phase 4.
- Supersedes: none. Superseded by: none.

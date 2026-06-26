# ADR 0020: Federated User Lifecycle (Deprovisioning & Revocation)

## Status

accepted (2026-06-26 — OQ1–5 resolved below)

**Extends ADR 0012 (Federation Participation Contract) — does not supersede it.** ADR 0012 §6
established just-in-time (JIT) user provisioning on first SSO login. This ADR adds the missing
*negative / out-of-band* half of the lifecycle — deactivate, reactivate, role-revoke, delete, and
immediate session revocation — for suite-member siblings. ADR 0012's JIT-on-login remains the
baseline for create and positive update; this ADR layers prompt deprovisioning on top.

This ADR introduces **no new compliance obligation**: it strengthens the data-custody posture of the
hosted edition (ADR 0015 §5) rather than creating any business or legal warranty, and it inherits the
2026-05-31 公認会計士・税理士 sign-off (#75). The data-custody legal review stays the **B6 terminal gate**
(ADR 0015 §5); OQ4 routes hard-purge there. A security review of the back-channel logout /
lifecycle-feed path (token forgery, replay, authz scoping) is an **implementation-time follow-up, not a
precondition** of this architecture decision — the same posture ADR 0012 took for its IdP key handling.

## Context

ADR 0012 settled the federation identity model:

- The suite is the identity provider (IdP) and holds the **organization roster + identity directory
  only** (§2, §11) — never sibling domain data.
- A sibling holds a **local user mirror**, created **JIT on first successful SSO login**, keyed by
  `email`, with suite role mapped to the sibling's own capability set (§6).
- Local authentication stays as a **break-glass fallback** and is not deleted on join (§6, §12).
- Hub-unavailability is **stale-tolerant, eventually consistent**; no daemon/cron is required for a
  sibling to stay functional (§10).
- Detach (leave) requires a confirmed working local admin to prevent lock-out (§12).

The gap this ADR closes: **JIT-on-login is lazy and only carries positive state.** Once a user is
mirrored, a *negative* change made in the suite directory — account disabled, role downgraded, user
removed from the org, account deleted — does **not** reach the sibling until that user's *next* login,
and the sibling's own local session (HMAC, sibling-owned, ADR 0012 §4) keeps authorizing its domain
APIs until that session expires. For a hosted edition where the suite is the identity authority,
"account disabled in the suite takes effect across the member tools" is a **trust- and
security-critical** property, and a **data-custody / 個人情報保護法** concern under ADR 0015 §5. Next-login
propagation is too weak for the negative path.

Constraints this decision must honor:

- **No cross-database writes.** The suite never writes a row into a sibling's database. Propagation is
  HTTP-only (orchestration-compliance §3 — a P0 defect to violate; ADR 0002).
- **NENE2 stays suite-agnostic.** Any sibling-side capability must be a **generic framework feature**
  — litmus test: *"would NENE2 add this to core even if the suite did not exist?"* — pinned to
  recognized provider-agnostic standards, and the upstream request must **never name the suite or an
  "orchestrator"** (recorded lesson: NENE2#1414 leaked such prose into the framework, fixed in
  NENE2#1417/#1418).
- **No new liveness dependency.** A sibling must keep working when the hub is down (ADR 0012 §10); the
  negative path may not require per-request calls to the suite.

## Decision

### 1. Scope — extend, don't replace

ADR 0012 §6 JIT-on-login remains the baseline for **create + positive update**. This ADR adds the
**negative / out-of-band lifecycle**: deactivate, reactivate, role-revoke, delete, and immediate
session revocation. The contract is **owned by the suite** (as in ADR 0012); the sibling gains a
**generic** capability and records its conformance in its own ADR (mirroring how NeNe Invoice
ADR 0016 conforms to ADR 0012).

### 2. Two-layer propagation — pull baseline, push optimization

**Layer 1 — pull (baseline, §10-consistent, the source of truth for convergence).** The suite
exposes a service-token-authenticated **user lifecycle delta feed** — the user-grain extension of the
ADR 0012 §5 organization roster pull. It is a cursor/`since`-based, monotonic list of user lifecycle
changes scoped to the requesting sibling's org(s). The sibling pulls on its own cadence (and
opportunistically at login) and reconciles its local mirror:

| Suite event | Sibling reconciliation |
| --- | --- |
| deactivate / suspend | disable local mirror user + invalidate its local sessions |
| reactivate | re-enable local mirror user |
| role change | re-map to local capability set (sibling owns the vocabulary, ADR 0012 §6) |
| remove-from-org / delete | **soft-disable** (never hard-delete when the user authored sibling domain records — mirrors §5/§11 org soft-disable) |

Pull is **best-effort, not a liveness dependency**: hub down → the sibling serves last state; no
daemon required. Convergence is guaranteed because the feed is monotonic and replays are idempotent.

**Layer 2 — push (immediate, security-critical events only, an optimization on top of pull).** For
events that must not wait for the next poll — primarily **deactivate/suspend** and
**credential/session revocation** — the suite makes a **best-effort back-channel call** to the
sibling's revocation endpoint with a **signed logout/revocation token** identifying the subject. Push
is never the source of truth: if it fails (sibling down, network), the Layer 1 feed still converges.
Because pull is the backstop, push needs **no guaranteed-delivery queue**.

### 3. Generic standard shapes (keeps NENE2 suite-agnostic)

The sibling-side capability is framed on recognized, provider-agnostic standards so it can be
requested as a pure framework feature that cannot name the suite:

- **Pull feed + reconciliation → SCIM 2.0 lifecycle semantics** (RFC 7643/7644): `active=false` /
  role patch / delete. SCIM is provider-agnostic by construction.
- **Push revocation → OIDC Back-Channel Logout**: a signed logout token, verified with the **same
  JWKS** the sibling already trusts for login assertions (ADR 0012 §3/§4) — no new trust root.

The framework feature is therefore "**honor an upstream IdP's account-lifecycle and back-channel
logout signals**" — something any IdP integration needs. The NENE2 issue is filed strictly as this
generic feature; it does not mention the suite, an orchestrator, or NeNe Suite naming.

### 4. Source of truth, join key, idempotency

- In suite mode the suite's identity directory is **SoT for identity + role**; the sibling mirror is a
  **projection** — read-only for federated fields, with edits routed to the suite (ADR 0012 §3).
- The durable **join key is `sub` (suite directory user id) + `org_external_id`**, not `email`. Email
  remains the *initial* link key at JIT (ADR 0012 §6) but is mutable; reconciliation uses the stable
  subject id.
- Both layers are **idempotent** and keyed by `sub` + `org_external_id`; replays and out-of-order
  delivery converge to the same state.

### 5. Deletion and detach

- **Delete = soft-disable** when the sibling holds domain records authored by the user (consistent
  with §5/§11 org soft-disable; never destroys domain data). A hard purge is a **separate, explicit,
  audited** operation governed by retention policy — not an automatic lifecycle event.
- **On detach** (ADR 0012 §12): lifecycle propagation stops, the sibling reverts to local auth
  (break-glass becomes primary), and the last mirror state is retained. Credential re-establishment at
  detach is per ADR 0012 §12 — the federated mirror has no usable password in suite mode.

### 6. Audit (ADR 0007)

Every **suite-side** lifecycle mutation (deactivate / reactivate / role-revoke / delete) records
before/after. A push send is an operator-visible action and is recorded with its result; advancing a
pull cursor is not a mutation and is not audited as one.

### 7. Edition gating

Like the federation key plane (milestone B1), the lifecycle plane is **federation/hosted-gated**
(`NENE_SUITE_EDITION` / `NENE_SUITE_MODE`): a clean OSS standalone build neither exposes the feed nor
pushes. Single-org OSS has no second tool to propagate to, so the plane is inert there.

### 8. Non-goals

- **Not a full SCIM server** — identity + role + active-state only in v1 (mirrors the ADR 0012 §4.1
  claim set); no group sync or arbitrary attribute schema.
- **Not per-request introspection** — the sibling never calls the suite per request; that would break
  §10 hub-down resilience and add latency.
- **No business/domain data ever flows** (ADR 0012 §2/§11; orchestration-compliance §3).

## Resolved at acceptance (2026-06-26)

- **OQ1 — push token signing → federation JWKS key.** The back-channel logout token is a short-lived
  JWT signed with the **federation signing key** and verified by the sibling via `NENE_SUITE_JWKS_URL`
  — the **same trust root** as the login assertion (ADR 0012 §3/§4), so no new key type and the same
  forgery defenses apply (alg-pin, `kid`, `aud`, `exp`, `jti` replay guard). The pull feed
  authenticates the sibling→suite direction with the §7 enrollment **machine service credential**. No
  new credential type is introduced.
- **OQ2 — freshness SLA → ≤ 5 min detectable; existing sessions bounded by a recommended TTL.** With
  push working, revocation is effectively immediate (best-effort, sub-second). When push cannot be
  delivered, the negative change is detectable within the pull interval (**≤ 5 min**) for *new*
  authorizations; for *already-issued* sibling local sessions the contract **recommends** (the sibling
  owns its session, ADR 0012 §4) a suite-mode access-token lifetime ceiling (≈ ≤ 15 min) with
  mirror active-state re-validation on refresh, so a missed push still closes within one refresh cycle.
  Suite does not mandate the sibling's session TTL; it sets the SLA target and the recommended ceiling.
- **OQ3 — role-change grain → reductions push, grants pull.** A privilege **reduction** (role
  downgrade / capability revoke) propagates via **push** (treated like deactivate — fail-safe). A
  privilege **grant** is **pull-lazy**: the user simply cannot exercise the new capability until the
  mirror catches up, which is safe. The security-relevant direction (reduction) is always prompt.
- **OQ4 — hard-purge authority → soft-disable only here; hard purge to B6.** This contract mandates
  **soft-disable** for delete (mirrors ADR 0012 §11 org soft-disable; a lifecycle event never destroys
  sibling domain data). A hard purge is a **separate, explicit, operator-initiated, audited** operation
  governed by retention policy and routed to the **B6 legal review** (ADR 0015 §5 — data custody /
  個人情報保護法). Deletion that could destroy domain records is a compliance decision, not an
  identity-lifecycle event.
- **OQ5 — sequencing → follow-on after B2.** The lifecycle plane is **not** part of B2's minimal login
  flow. It ships as a later federation slice once its foundations exist: B1 keys (landed), B2 org
  resolution, and the ADR 0012 §5 roster-pull + §7 enrollment surface (it is the **user-grain
  extension** of roster-pull). Building it before those is out of order.

## Terminology registry impact (ADR 0006)

This contract **coins no new canonical identifier**: propagation rides recognized standard shapes
(SCIM 2.0 `active` / role patch / delete; OIDC Back-Channel Logout token) and reuses already-registered
suite identifiers (`NENE_SUITE_JWKS_URL`, `NENE_SUITE_EDITION`, `NENE_SUITE_MODE`, claims `sub` /
`org_external_id`). The lifecycle transitions named above (deactivate / reactivate / revoke /
soft-disable) are **prose descriptions of standard transitions, not Suite-coined enums**.

Endpoint identifiers register **at implementation, with the surface they extend** (the ADR 0018
precedent — Suite registers only what it serves/consumes, when it is built):

- The Suite-**served** lifecycle delta feed registers with the **ADR 0012 §5 organization roster-pull
  API** (both unbuilt; the feed is its user-grain extension).
- The sibling-**published** back-channel logout endpoint is a **NENE2-side identifier**, registered
  when NENE2 implements the generic feature (cross-repo, like ADR 0018's `/machine/update`) — in
  NENE2's own registry, never Suite-aliased.

Reaffirmed: the federated subject join key is `sub` + `org_external_id`; `suite_org_id` stays
prohibited (terminology §6). **No change to `docs/explanation/terminology.md` is required at acceptance.**

## Consequences

**Benefits.** Account disable / revoke takes effect across member tools **promptly** (push) with a
**guaranteed-converging backstop** (pull); a strong trust + 個人情報保護法 / data-custody posture for the
hosted edition; stays **HTTP-only and §3-clean** (no cross-DB writes); NENE2 stays **suite-agnostic**
(generic SCIM + back-channel logout). Reuses the ADR 0012 JWKS trust root — no new key material.

**Costs / new work (dependent on B1 keys + B2 org resolution).**

- Suite: a user-grain **lifecycle delta feed** (extends the §5 roster pull) + a **back-channel logout
  sender**; before/after audit on each mutation.
- NENE2 (generic framework feature, filed suite-agnostic): honor an upstream IdP's account-lifecycle
  (SCIM-shaped) + back-channel logout (OIDC-shaped) — disable the local mirror + revoke local sessions.
- Terminology registration; a security review of the logout-token path (alg-pin, `aud`, replay).

**Risks.**

- A **forged logout/revocation token** could mass-disable users → mitigated by JWKS alg-pin + `aud` +
  short TTL + `jti` replay guard (the same posture as the login assertion, ADR 0012 §3).
- **Push without the pull backstop would be unreliable** → pull is the mandatory baseline; push is an
  optimization, never the source of truth.

## Related

- **Extends (does not supersede): ADR 0012** §5 (roster pull), §6 (JIT provisioning), §10 (hub-down
  resilience), §11 (compliance guardrails), §12 (detach lock-out prevention).
- ADR 0002 (orchestrator, not monolith), orchestration-compliance §3 (separate DBs, HTTP only),
  ADR 0007 (audit before/after), ADR 0015 §5 (data portability / custody), ADR 0006 (terminology).
- Milestone `docs/milestones/2026-06-multi-tenant-suite.md` — B1 (federation key plane), B2
  (assertion mint + org resolution); this plane depends on both.
- Standards: SCIM 2.0 (RFC 7643 / 7644), OIDC Back-Channel Logout.
- NENE2: generic framework feature request (issue TBD) — suite-agnostic; never name the suite
  (lesson: NENE2#1414 → NENE2#1417/#1418).
- Issue: `#275`. PR: `#276`.
- Superseded by: none.

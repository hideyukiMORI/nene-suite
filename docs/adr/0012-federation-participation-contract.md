# ADR 0012: Federation Participation Contract (sibling ↔ suite membership)

## Status

proposed (draft — supersedes part of ADR 0004; not accepted until terminology
registration + professional/governance sign-off land)

## Context

A sibling product (starting with NeNe Invoice) can run in two ways:

1. **standalone** — installed on its own, with local authentication and its own
   organization records.
2. **suite member** — joined to a NeNe Suite hub for single sign-on, a shared
   organization roster, and apex navigation.

The operator requirement is that a standalone install can later **join** a suite,
and a suite member can later **leave** and return to standalone, **without moving
any product data** and **without destructive operations** in either direction.

This contract is **owned by NeNe Suite** (the hub). Sibling repositories conform to
it; they do not define it. NeNe Invoice records its conformance + local enforcement
in its own ADR 0016, which references this document and does not finalize until this
ADR is `accepted`.

### Prior art already in this repository (must be reconciled, not reinvented)

The terminology registry (ADR 0006) and suite environment contract (ADR 0004)
already define federation vocabulary:

- `NENE_SUITE_MODE` — standalone (`unset`/`0`) vs suite mode (`1`). This **is** the
  membership toggle; this ADR does not introduce a new flag.
- JWT claims for suite mode: `sub`, `org_id` (local PK), `org_external_id`
  (federation UUID), `suite_id` (installation id).
- Federation organization identifier: `org_external_id` (claim) /
  `NENE_SUITE_ORG_EXTERNAL_ID` (env) / `organizations.external_id` (sibling DB
  column). The spelling `suite_org_id` is **explicitly prohibited** (terminology §5/§6).
- A **shared HMAC** primitive: `NENE_SUITE_JWT_SECRET` copied into each sibling's
  `NENE2_LOCAL_JWT_SECRET` (terminology §4.2).

Two reconciliations follow from the cross-repo design discussion:

- The federation link attribute the sibling stores is the **already-registered**
  `organizations.external_id` (value `org_external_id`), **not** a new `suite_org_id`.
- The shared-HMAC primitive is **insufficient** for the agreed requirement of JWKS
  key rotation and blast-radius containment (a shared symmetric secret lets any one
  member forge assertions for all members). This ADR moves suite-issued assertions
  to **asymmetric signing with a published JWKS**, superseding the HMAC usage in
  ADR 0004 §(JWT secret) and terminology §4.2 for the login-assertion path.

## Decision

### 1. Membership is a reversible mode toggle; no data moves

- Membership is expressed by `NENE_SUITE_MODE` plus federation config written to the
  sibling's apex `.env`. Join and leave are **configuration changes only**.
- The sibling database is **never** copied to or shared with the suite. The suite
  holds an **organization roster + identity directory only** — never issued
  documents, document numbering, issuer profiles, payments, or any domain data.
- Both directions are **non-destructive**: no merge, split, renumber, or hard delete
  of organizations or domain records ever results from join or leave.

This is consistent with ADR 0002 (orchestrator, not monolith) and
orchestration-compliance §3 (separate databases, HTTP only).

### 2. What the suite centralizes (and what it does not)

In scope for the hub: **SSO / login**, **organization registry** (roster +
federation UUID authority), and **launcher / service discovery**.

Explicitly **out of scope**: any aggregated view or store of sibling domain data
(billing figures, invoices, reconciliation, archive). The hub federates identity and
navigation; it does not federate business records.

### 3. Login protocol — asymmetric authorization-code assertions + JWKS

Decision (resolving cross-repo open point #7): the suite is the identity provider
using a **browser authorization-code redirect flow** that issues a **short-lived,
asymmetrically signed login assertion**, with key material published at a **JWKS
endpoint** and rotatable. This is OIDC-*shaped* but intentionally minimal: the suite
implements the authorization-code redirect + token + JWKS surface, **not** a full
OIDC server (no dynamic client registration, userinfo, or scope negotiation in v1).

Constraints (required by NeNe Invoice, accepted here as contract invariants):

1. **Authorization-code shape is mandatory.** No implicit flow, no hand-rolled
   credential POST. Even the minimal implementation keeps the authorization-code
   skeleton (the auth flow is not where we economize).
2. **Suite assertions authenticate login only — never authorize a domain API.** The
   sibling exchanges a valid suite assertion for its **own** local session (NeNe
   Invoice ADR 0014: in-memory JWT + httpOnly refresh) and guards its domain APIs
   with that local session. A leaked suite assertion therefore cannot reach billing
   APIs directly — fail-closed is preserved.
3. **Rotation.** Keys are published via JWKS with `kid`; the suite may rotate without
   coordinated sibling redeploys. Siblings cache JWKS and refresh on unknown `kid`.

Upgrade path: if a future requirement adds non-NeNe relying parties, the minimal
surface promotes to full standard OIDC without changing the claim contract below.

### 4. Two tokens, two trust domains, two key types

Federation introduces a **second, distinct** token. It does not change the sibling's
existing local session. The two must never be conflated.

| Token | Signing | Key | Purpose | Owner |
| --- | --- | --- | --- | --- |
| **Suite federation assertion** | **asymmetric** | suite private key; siblings verify via published JWKS (public only) | authenticate an SSO login, single-hop, exchanged immediately | suite (this ADR) |
| **Sibling local session** | HMAC | `NENE2_LOCAL_JWT_SECRET` — **sibling-generated, sibling-local**; the suite does **not** distribute it | guard the sibling's domain APIs | sibling (e.g. NeNe Invoice ADR 0014) |

The sibling holds **no federation signing key** — it only verifies suite assertions
with public JWKS material. Key authority for federation is centralized in the suite.

**4.1 Suite federation assertion — claim set**

| Claim | Canonical name | Notes |
| --- | --- | --- |
| Subject | `sub` | Suite identity directory user id |
| Federation org UUID | `org_external_id` | Suite-minted org UUID (see §5) |
| Installation id | `suite_id` | Suite installation identifier |
| Role | suite role claim (registered in terminology — see Impact) | Coarse suite role |
| Email | `email` | For local user linking (§6) |
| Issuer | `iss` | `NENE_SUITE_ISSUER_URL` |
| Audience | `aud` | Target sibling installation |
| Expiry | `exp` | Short TTL |

The assertion **never** carries `org_id` — the suite does not know sibling-local
primary keys. It asserts `org_external_id`; the sibling resolves it to its local org.

**4.2 Sibling local session — claim set (sibling-owned, shown for contract clarity)**

- **standalone mode**: `sub`, `org_id` (local PK). **No federation claims.**
- **suite mode**: `sub`, `org_id` (local PK), and **mirrors** `org_external_id` (and
  `suite_id` if the sibling needs it) resolved at exchange time.

The flow: the sibling receives a valid suite assertion (§4.1), then **mints its own
local session** (§4.2) and uses only that to authorize domain APIs.

**4.3 Claim optionality (confirmed contract invariant).** `org_external_id` and
`suite_id` are **present only in suite mode** and are **absent/null in standalone**.
The claim contract explicitly permits a standalone sibling local token to carry no
federation claims. This is what makes `NENE_SUITE_MODE` a clean toggle.

The registered "JWT claims (suite mode)" set in terminology §5 (which includes
`org_id`, a local PK) describes the **sibling local session in suite mode** (§4.2) —
**not** the suite assertion (§4.1). Terminology §5 is clarified accordingly (see
Impact).

### 5. Organization UUID authority — asymmetry fixed in schema

- The sibling's **canonical** organization identifier is its **local org id**
  (immutable; the anchor for issued documents and numbering). This never changes on
  join or leave.
- The **federation** identifier is `organizations.external_id` (value
  `org_external_id`), a **nullable** link attribute on the sibling org row:
  - **Suite-first** (suite provisioned the sibling): the suite mints
    `NENE_SUITE_ORG_EXTERNAL_ID` and the sibling stores it on the new org row at
    provision time.
  - **standalone-first join**: the existing local org gets `external_id` populated
    after the fact (1:1 link). Issued documents and numbering are untouched.
- **1:1 only — merge is impossible by construction.** Issued documents are bound to
  the local org id; even if the suite upstream merges two orgs, the sibling cannot
  collapse two local rows with distinct issued histories. Billing/compliance always
  reference the local id; federation/SSO reference `org_external_id`. The two paths
  are separate.
- **Hard delete of an org is prohibited** when issued documents exist; the suite may
  only request **soft-disable**.

### 6. Member provisioning (JIT) and user linking

- Users created in the suite directory are provisioned in the sibling **just in
  time**: on first successful suite-assertion login, the sibling creates the local
  user, keyed by `email`, and maps the suite role claim to the sibling's own
  capability set (the sibling owns its capability vocabulary; suite roles are coarse
  and locally mappable, with local override allowed).
- Local authentication remains as a **fallback / break-glass** path and is not
  deleted on join.

### 7. Enrollment (join handshake)

The suite exposes a **one-time enrollment token → credential exchange** endpoint. The
operator initiates join from the sibling by entering the hub URL + a one-time
enrollment token; the sibling exchanges it for (a) a machine service credential and
(b) the suite IdP configuration (issuer URL, JWKS URL, audience). This endpoint is
**new suite work** (the existing apex operator/service-token surface is the basis but
the inbound enrollment exchange does not yet exist).

### 8. Self-registration of externally-installed siblings

The current suite model assumes the suite **installed** the sibling (install manifest,
ADR 0010). A standalone-first join is the **reverse**: a sibling the suite did not
install registers itself inbound. The install-manifest / installed-apps model is
extended to accept an **externally-installed app registering into the suite**. This is
**new suite work** and a prerequisite for standalone-first join.

### 9. Discovery contract (sibling-published)

A suite member sibling publishes a minimal, service-token-authenticated
**health + capabilities** endpoint so the hub can discover and link to it. The suite
consumes this; it does not implement the sibling's domain APIs (integrations doc,
dependency direction unchanged).

### 10. Hub-unavailability behavior (contract-level)

| Situation | Behavior |
| --- | --- |
| Hub unreachable during an existing session | **Continue.** The sibling already holds its own session and does not call the hub per request; refresh is local. |
| New login while hub is down | SSO unavailable → fall back to **local password (break-glass admin)**. |
| Organization roster sync | **Stale-tolerant, eventually consistent.** The sibling serves from the last synced mirror; only new orgs/members are delayed until the hub returns. No daemon/cron is required to keep a sibling functional. |

Result: existing users and orgs remain fully operational while the hub is down. The
hub is only required for *first-time login of a new user* and *roster changes*.

### 11. Compliance guardrails (non-negotiable, both sides)

- The sibling is SSOT for its domain (issued documents, numbering, issuer profile,
  payments). These are **never** delegated to or stored in the suite.
- The suite holds organization roster + identity directory only.
- No org merge / split / renumber. Organization removal is **soft-disable only** when
  issued documents exist.
- `org_external_id` is never reused as, or derived from, a registration number
  (terminology §6).

### 12. Detach (leave) lock-out prevention

Before a member may leave, the sibling **must confirm a working local admin
credential** (break-glass), so loss of the IdP cannot lock an operator out. On leave:
`NENE_SUITE_MODE` returns to standalone, login reverts to local password, the last
synced org names are retained, federation link ids are deactivated (not deleted), and
the hub↔sibling service token is revoked. Documents and numbering are untouched.

## Terminology registry impact (must land in the same PR before merge — ADR 0006)

This ADR introduces or changes registered identifiers; the terminology registry
**MUST** be updated in the same PR:

- **Add**: JWKS endpoint identifier (e.g. `NENE_SUITE_JWKS_URL`), the suite role claim
  name, the enrollment-token identifier, and the assertion `aud`/`iss` usage.
- **Change**: the login path moves from shared HMAC to asymmetric + JWKS. The §4.2
  coupling "suite copies `NENE_SUITE_JWT_SECRET` into each app's
  `NENE2_LOCAL_JWT_SECRET`" is **superseded**: `NENE2_LOCAL_JWT_SECRET` is now a
  **sibling-generated, sibling-local** session key that the suite does not distribute;
  federation uses a separate asymmetric suite key. Retain `NENE_SUITE_JWT_SECRET` only
  if still used for non-login machine paths; otherwise mark superseded.
- **Clarify §5**: the registered "JWT claims (suite mode)" set (with local-PK `org_id`)
  describes the **sibling local session in suite mode**, not the suite federation
  assertion. The suite assertion claim set is §4.1 of this ADR.
- **Reaffirm**: `org_external_id` / `organizations.external_id` is the only federation
  org identifier; `suite_org_id` stays prohibited.

## Consequences

**Benefits.**

- One suite-owned normative contract; the sibling ADR is a thin conformance reference,
  preventing two-repo drift.
- Join/leave are reversible config toggles with zero data movement and no destructive
  operations.
- Asymmetric signing contains blast radius (a compromised member cannot forge suite
  assertions for others) and enables key rotation.
- Compliance invariants (local SSOT, no merge, soft-disable) are enforced by schema
  and contract, not convention.

**Costs / new suite work (tracked as dependent Issues, blockers for sibling ADR 0016).**

- Asymmetric key management + JWKS endpoint + authorization-code redirect surface
  (supersedes the HMAC env path).
- Inbound enrollment-token → credential exchange endpoint (§7).
- Externally-installed app self-registration extending the install-manifest model (§8).
- Organization roster pull API with soft-disable semantics (§5).
- Terminology registry updates (above).

**Risks.**

- Superseding the HMAC model touches ADR 0004 and the env contract already shipped;
  must be sequenced so existing dev/test (no federation) is unaffected.
- Suite becomes a security-sensitive IdP; key handling and rotation need review under
  orchestration-compliance.

## Related

- Issue: `#94`
- Supersedes (in part): ADR 0004 (suite environment contract — JWT secret / HMAC
  login path), terminology §4.2.
- ADR 0002 (orchestrator, not monolith), ADR 0005 (orchestration compliance binding),
  ADR 0006 (terminology registry binding), ADR 0007 (audit trail), ADR 0010 (install
  manifest persistence), ADR 0011 (control database URL resolution).
- `docs/integrations/sibling-products.md` (organization federation, service tokens).
- `docs/explanation/terminology.md` §4 (env vars), §5 (JWT claims), §6 (org federation).
- Sibling conformance: nene-invoice ADR 0016 (references this contract; does not
  finalize until this ADR is `accepted`), nene-invoice ADR 0017 (export/import-install
  — independent, pure-local migration/DR).
- Superseded by: none.
</content>
</invoke>

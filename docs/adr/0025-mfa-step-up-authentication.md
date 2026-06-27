# ADR 0025: MFA / Step-Up Authentication

## Status

accepted

## Context

NeNe Clear's adoption review flagged **multi-factor authentication (MFA) as a P1
enterprise blocker** (nene-clear#195). Clear was about to implement MFA on its own, then
recognized that authentication is **federated** (Suite is the IdP) and raised a cross-repo
question (#341): where does MFA live, who handles enroll vs enforce, and does it depend on
Clear joining federation?

Established facts (all confirmed against the code / ADRs):

- **Suite is the IdP** (ADR 0012 §2/§3): SSO / login and the organization registry are
  centralized; siblings exchange an SSO assertion for their own local session (§4, two
  tokens). Sibling users are **JIT-mirrored** by `email` with **local auth kept as a
  break-glass fallback** (§6). Lifecycle / logout is ADR 0020.
- **MFA / step-up is deferred at B1.2** — `src/Auth/LoginRateLimiter.php` notes that a
  per-email velocity signal needs an escalation primitive (CAPTCHA / step-up) that does not
  exist yet, recorded in the B1.2 milestone row.
- **Siblings can run standalone** (ADR 0014) with their own local auth, **or federated**
  (ADR 0012). All siblings inherit **NENE2** (ADR 0008). NENE2 already ships auth primitives
  (`TokenIssuerInterface`, `LocalBearerTokenVerifier`, `SecureTokenHelper`) and a
  `totp-authentication` how-to — but no reusable TOTP *code*.

The owner's decision: **support MFA in both deployment modes** (standalone and federated) —
do not make standalone a permanently MFA-less tier.

## Decision

1. **No new authentication repository.** The shared layer is **NENE2**. A generic,
   Suite-agnostic **TOTP primitive** (verify / secret / recovery-code helpers) is added to
   NENE2 — promoting the existing how-to (NENE2#1427). Apps keep their own enroll / challenge
   flow and enforcement policy.

2. **MFA is supported in both modes — one mechanism, different location.**
   - **Standalone**: the sibling's **local login** uses the NENE2 TOTP primitive (the only
     place that sees a password).
   - **Federated**: the **Suite IdP enforces** MFA at login; the SSO assertion carries a
     **step-up-satisfied claim** (OIDC `amr` / `acr` style); the sibling trusts the assertion
     and runs **no local MFA**.

3. **enroll vs enforce.** `enroll` = the user's own device; `enforce` = an administrator
   policy. **User-optional ON/OFF is rejected** (optional MFA only protects those who opt in).
   Enforcement policy lives in the **app layer** — the Suite IdP holds it **per suite-org**
   for federated; the sibling holds it **per deployment** for standalone. NENE2 holds the
   **mechanism only** and learns no Suite concepts.

4. **Break-glass is MFA-exempt, with compensating controls.** Recovery codes are **mandatory
   at enrollment** (all modes). Federated break-glass admin (ADR 0012 §6) is **IP-restricted +
   audited + limited scope**. Standalone break-glass is a **server / CLI MFA-disable command**
   (server-access holder only) **+ audited**.

5. **MFA is decoupled from the federation roadmap.** A sibling does **not** wait for federation
   to ship standalone MFA — it implements the NENE2 TOTP recipe now. When it later federates,
   login moves to the IdP and MFA moves with it (the sibling retires its local MFA path; the
   standalone code remains for never-federated deployments).

6. **The step-up claim is app-layer, not NENE2.** Suite's `AssertionTokenIssuer` adds the
   step-up-satisfied claim; the sibling's `AssertionTokenVerifier` reads it. NENE2 provides
   only the generic TOTP primitive plus the JWT / JWKS plumbing it already has.

7. **TOTP secrets ride on the app's at-rest encryption** (e.g. NeNe Clear's, shipped in
   nene-clear#195). NENE2's primitive never persists a secret itself.

## Consequences

**Benefits.**

- One verification mechanism — no duplicated, security-critical TOTP crypto across apps.
- No new repository; NENE2 stays generic.
- MFA available in **both** modes; the enterprise blocker is addressed for self-hosted
  standalone deployments, not only hosted / federated ones.
- Decoupled from the federation timeline, so Clear can close its P1 gap independently.

**Costs.**

- The MFA surface exists in **two contexts** (sibling local login + Suite IdP) — both must be
  maintained and security-reviewed.
- Standalone lockout risk (lost authenticator) — mitigated by **mandatory recovery codes** +
  the CLI break-glass.
- Standalone MFA code becomes legacy for a deployment that later federates (but lives on for
  standalone-forever deployments).

**Follow-up.**

- **NENE2#1427** — generic TOTP primitive (Suite-unnamed).
- **nene-clear#195** — Clear implements standalone TOTP (enroll + verify + mandatory recovery
  codes + admin / CLI reset) on the NENE2 recipe; secrets on its at-rest encryption.
- **Suite IdP MFA enforcement** lands with federation enablement (B2 neighborhood; closes the
  B1.2 step-up deferral) — the step-up-satisfied assertion claim and IdP-side standardization
  (recovery, admin reset, ADR 0020 back-channel logout integration).

## Related

- Issue: `#341` (cross-repo question from NeNe Clear)
- Extends: [ADR 0012](0012-federation-participation-contract.md) (Federation Participation Contract)
- Relates: [ADR 0014](0014-schema-migration-lifecycle.md) note on standalone operation,
  [ADR 0008](0008-inherit-nene2-coding-standards.md) (NENE2 inheritance),
  [ADR 0020](0020-federated-user-lifecycle.md) (federated user lifecycle / back-channel logout)
- Cross-repo: NENE2#1427 (generic TOTP primitive), nene-clear#195 (MFA + at-rest encryption)
- Supersedes: none
- Superseded by: none

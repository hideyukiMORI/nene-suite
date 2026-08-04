# Federation signing key management (hosted edition)

Operational runbook for the federation IdP signing keys that sign ES256 SSO assertions
([ADR 0012](../adr/0012-federation-participation-contract.md) §3/§4; milestones B1.4–B1.8).
**Hosted edition only** — a self-hosted (OSS) install has no federation key plane (the services and
the JWKS route are not constructed; `ops/keys/*` and the boot preflight self-skip).

> **Keep this page host-independent.** It documents the key lifecycle, not a particular
> deployment. If you need to record real values — hostnames, server paths, key ids, rotation
> dates, who is on duty — those belong in the non-public infrastructure runbook, not here. This
> file is a likely place for such values to accumulate over time, which is why the rule is
> written down rather than assumed. Same split as
> [`staging-deploy.md`](staging-deploy.md).

## Model

- The suite holds the **public** key only, in `federation_signing_keys` (`kid`, `public_jwk`,
  `status`, timestamps). The **private key never enters the DB or the audit trail** — the operator
  holds it out of band as `NENE_SUITE_FEDERATION_PRIVATE_KEY` (or `..._FILE`).
- `kid` is the RFC 7638 JWK thumbprint, so it is deterministic from the key.
- Status lifecycle: `active` (signs new assertions) → `retiring` (no longer signs, still published in
  JWKS during the grace window) → `retired` (dropped from JWKS); `revoked` is an emergency state that
  drops the key from JWKS immediately. **Exactly one `active`** at a time.
- The JWKS endpoint `GET /.well-known/jwks.json` publishes `active` + `retiring` public keys.

## Environment

| Variable | Notes |
| --- | --- |
| `NENE_SUITE_EDITION=hosted` | Required for any of this to be active. |
| `NENE_SUITE_FEDERATION_PRIVATE_KEY` | The active key's private PEM (secret). Or `..._FILE` to read from a mounted secret. |

The boot preflight (`ops/docker/preflight-federation-key.php`, run from the entrypoint) **fails
closed** if, in the hosted edition, the private key is missing/unloadable or its `kid` does not match
the active published key — preventing a silent total-SSO-outage from signing with an unpublished kid.

## Procedures

All commands are operator-run and hosted-only; they refuse in OSS.

### First-time generation

```
docker compose run --rm suite php ops/keys/generate-federation-key.php
```

Stores the public JWK as the single `active` key and prints the `kid` + private PEM **once**. Set the
private PEM as `NENE_SUITE_FEDERATION_PRIVATE_KEY` and (re)start the apex. Refuses if an active key
already exists (use rotation instead).

### Routine rotation

```
docker compose run --rm suite php ops/keys/rotate-federation-key.php
```

Demotes the current active key to `retiring`, mints a new `active`, and retires any prior retiring
key. Then:

1. Set the printed new private key as `NENE_SUITE_FEDERATION_PRIVATE_KEY` and restart the apex.
2. **Keep the retiring key published for ≥ the assertion TTL** so assertions signed just before the
   switch still verify. Both keys are in the JWKS until the next rotation retires the old one.

Rotation is operator-driven, **never on boot** — rotating per container restart would churn the
`kid` faster than siblings refresh their JWKS cache.

### Emergency revocation (compromise)

```
docker compose run --rm suite php ops/keys/revoke-federation-key.php <kid>
```

Marks the key `revoked` and drops it from the JWKS immediately. If the compromised key was active,
generate a new one and update `NENE_SUITE_FEDERATION_PRIVATE_KEY`.

## The recovery-window caveat (read before relying on revocation)

Siblings **cache the JWKS** and only refresh on encountering an **unknown** `kid` (ADR 0012 §3). So a
revoked-but-still-cached `kid` may keep verifying at a sibling **until that sibling's JWKS cache
expires** — the real compromise-recovery window is bounded by the **JWKS `Cache-Control` max-age**
(currently 300s on `GET /.well-known/jwks.json`), **not** by the revoke instant and **not** by the
short assertion TTL. To shrink the window, lower the JWKS cache max-age. Revocation + short cache +
short assertion TTL together bound exposure; revocation alone is not instantaneous across the
federation. Every generate/rotate/revoke is recorded in the suite audit trail
(`federation_signing_key.*`, [audit-trail §4](../explanation/audit-trail.md)).

# Host-side deploy agent — consume/report contract (S2-1a)

**Status: binding for the deploy seam.** This is the contract between the suite and the
**host-side deploy agent** decided in [ADR 0019](../adr/0019-tier-b-deployment-driven-upgrade.md)
OQ1: the suite never holds the Docker socket; a process on the compose host executes
`compose pull` + recreate against an explicit allow-list and reports the result through a
narrow, audited machine seam. S2-1a ships the seam (queue + this contract); the
dependency-ordered plan (S2-1b), chain execution with post-recreate verification (S2-1c),
and the apex UI (S2-1d) build on it.

**Related:** [`.env.suite.example`](../../.env.suite.example) (deploy agent section),
[`audit-trail.md`](../explanation/audit-trail.md) §4 (`deploy_request.*`),
[`terminology.md`](../explanation/terminology.md) §4.5.

---

## 1. Capability flag (default off)

| Env (suite container) | Meaning |
| --- | --- |
| `NENE_SUITE_DEPLOY_AGENT_ENABLED=1` | Opt-in. Exact string `1`; anything else = disabled |
| `NENE_SUITE_DEPLOY_AGENT_KEY` | Shared pairing secret, **≥32 bytes**. The agent sends it verbatim in `X-NENE-SUITE-DEPLOY-KEY` |

Fail-closed: flag not `1` **or** key shorter than 32 bytes → the capability resolves
disabled. Disabled means the operator surface returns 409 (`deploy-capability-disabled`),
the machine surface refuses, no `deploy_requests` rows are ever created, and the UI
degrades to "updates visible, apply manual" — the same posture as the Origin client.
Available to **every edition** (OSS self-host included).

## 2. Seam endpoints (agent side)

Authentication: `X-NENE-SUITE-DEPLOY-KEY: <key>` on every call (constant-time compared;
401 `deploy-agent-unauthorized` on mismatch, 409 while the capability is off).

1. **Poll** — `GET /api/v1/machine/deploy/requests/pending`
   → `{ "requests": [ { "id", "service", "imageDigest", "status": "pending", … } ] }`,
   oldest first. Poll interval is the agent's choice (suggested 15–60 s; this is a local
   HTTP call).
2. **Execute** — for each request, in order:
   - **Allow-list check (agent-side, mandatory).** `service` must be in the agent's own
     configured service list (mirroring `catalog/apps.json` ids). The suite validates on
     create too, but the agent must not trust the queue blindly — defense in depth.
   - `docker compose pull <service>` at the **digest** (`image@sha256:…`) and
     `docker compose up -d <service>` — recreate only; never `down`, never volume changes,
     never any other service.
3. **Report** — `PUT /api/v1/machine/deploy/requests/{id}/result` with
   `{ "status": "succeeded" | "failed", "detail": "<≤4000 chars, no secrets>" }`
   → 200 with the terminal request. 409 (`deploy-request-conflict`) means the request was
   already reported — **do not re-execute**; treat it as consumed and move on.

## 3. Invariants (from ADR 0019 — the agent MUST hold these)

- **Allow-listed services only, `pull` + recreate only.** No exec into containers, no
  file edits inside a sibling's tree, no compose file mutation.
- **Exactly one agent per compose host.** The seam has no claim step in S2-1a; two
  concurrent consumers would double-execute. (A claim step is added if this assumption
  ever breaks.)
- **Report everything.** A request the agent started but could not finish is reported
  `failed` with detail — never left `pending` silently.
- **No secrets in `detail`.** The value lands verbatim in the audit trail.
- The suite independently verifies outcomes via the sibling's auth-gated
  `/machine/health` version probe (S2-1c) — a `succeeded` report is not trusted blindly.

## 4. Audit

Every transition is recorded in `suite_audit_events` (ADR 0007): `deploy_request.created`
(operator actor, `source: apex_admin`) and `deploy_request.completed` (machine actor,
`actor_label: deploy-agent`, `source: api`) with before/after snapshots.

## 5. Reference implementation

A reference poller (`ops/deploy-agent/`) ships with a later slice; any implementation
honoring §2–§3 conforms. The existing `ops/staging/deploy-staging.sh` host pattern (same
trust domain) is the intended home.

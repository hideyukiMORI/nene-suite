# Frontend attack-surface inventory — NeNe Suite

A one-sheet map of the browser-facing attack surface of the Suite SPA, the control
in place for each, and its automated coverage. This is **T3 groundwork**: an
inventory of implemented defenses and where they are tested — not an exploitation
guide and not a penetration test. Un-landed hardening is summarized under *Planned*
(and tracked in detail out-of-band with the fleet hub, per the public-repo policy).

Verdict legend: **✅ control in place + tested** · **▫️ control in place, dedicated
test planned** · **→ T3** (planned hardening, see [`README.md`](README.md)).

## Surface map

| Surface | Vector | Control in place | Coverage |
|---|---|---|---|
| **DOM rendering** | Stored/reflected XSS via app-rendered content | React default escaping; **no `dangerouslySetInnerHTML` / `innerHTML` sink** in app code | ✅ (structural — no sink exists) |
| **Token at rest (browser)** | Token theft widening via persistent storage | Bearer token in `sessionStorage` only (tab-scoped, cleared on close); **never `localStorage`** | ✅ `auth/model.test.ts` (*never writes to localStorage*) |
| **Session validity** | Use of an expired/again-valid session | `isAuthenticated()` rejects past `expiresAt`; server-side revocation on logout | ✅ `auth/model.test.ts` + backend `PdoRevokedTokenRepositoryTest` |
| **Route authz** | Reaching a gated view unauthenticated / under-privileged | Fail-closed guards `RequireAuth` → `/login`, `RequireSuperadmin` → `/` | ✅ `app/auth-gate.test.tsx` |
| **Session claims** | Privilege inference from a malformed/legacy session | Mapper fails closed: `superadmin ?? false`, `role ?? null`, `orgExternalId ?? null` | ✅ `auth/mapper.test.ts` |
| **API error channel** | HTML/error-page injection through the fetch layer | RFC 9457 Problem Details; transport never surfaces non-2xx as raw HTML | ✅ `shared/api/errors.test.ts`, `client.test.ts` |
| **Authz regression** | Silent privilege drift as routes/roles evolve | Fail-closed guards above are the foundation | → T3 (role × route matrix) |
| **Supply chain** | Vulnerable transitive dependency | Lockfile-pinned installs | → T3 (dependency audit in CI) |
| **Transport hardening** | Browser-side hardening headers for the served SPA | — | → T3 (response-header baseline) |

## Notes

- The **fail-closed default** is the through-line: unauthenticated, under-privileged,
  and malformed-session cases all resolve to the least-privilege outcome (login /
  home / non-superadmin / no active org), and each has a regression test.
- Coverage above is the **T1-lite** result (entity/pure-logic + auth-gate). The
  `→ T3` rows are the next investment; their sequencing and any live-fire scope sit
  with the fleet hub and await the maintainer decision on L3/L4.
- This sheet lists surfaces and **controls**, deliberately not un-remediated gaps —
  the repo is public. The sharp gap list is the hub's internal T3 working note.

# ADR 0017: Origin client consumption contract (Suite ↔ NeNe Origin)

## Status

accepted

**Revised 2026-06-24 (Topic 2).** The read model is now a **profiled TUF** model
(Origin [ADR 0006](https://github.com/hideyukiMORI/nene-origin)); this revision **supersedes the
flat shape** recorded in the original 2026-06-23 version (a single `manifest` object + a JWKS
`key_set`). The signing/transport primitive and the §1 SSOT split are unchanged.

## Context

NeNe Suite's dashboard surfaces three Origin-fed signals per installed app — **available
updates**, **announcements**, and **house-ads** — mirroring the Adobe Creative Cloud launcher.
[ADR 0013](0013-update-aggregation-and-upgrade-orchestration.md) established Suite as the optional
**aggregator/upgrade orchestrator** but deferred the concrete wire contract to "a Suite-side ADR
when implementation lands". This ADR is the **consumer** record (owner: nene-suite); Origin owns
the authoritative spec (`nene-origin/docs/spec/`).

**Two rounds of cross-repo agreement** (notes archived at
`/home/xi/docker/huddle-suite-and-origin/`):

- **Topic 1 (2026-06-23)** agreed a flat read model: `.jws` detached sidecar (RFC 7515 + RFC 7797
  `b64:false`), `?channel=`, locale fallback, per-product fan-out.
- **Topic 2 (2026-06-24)** re-agreed the **read model itself**. After a 20-persona adversarial
  design review, Origin recorded **ADR 0006 "signed static read model = profiled TUF"**, which
  **keeps the Topic 1 transport/signing primitive** but **changes the object structure** to a
  vetted hierarchy with defenses against rollback / freeze / mix-and-match. Both sides are
  **pre-launch** (no client or signing CLI implemented, nothing published), so the model is
  switched **cleanly in `/v1`** — no `/v1`→`/v2` migration, no compatibility window.

Layering recap (ADR 0013): Origin is the private signing authority; each product ships a thin
client; Suite optionally aggregates. Suite must never become required for a standalone product
(scope contract).

## Decision

### 1. Source of truth (SSOT) — unchanged

- **Suite `catalog/apps.json` owns the app roster** and the display metadata
  (`icon` / `description` / `category` / `min_suite_version` / `deprecation`).
- **Origin owns per-product signals only** — version/update, announcements, house-ads. There is
  **no global "all apps" endpoint**; Suite fans out per product slug from its own roster.

### 2. Read model — profiled TUF (static, signed, no read server)

Base = `NENE_ORIGIN_URL`, all under `/v1`, signed **static** objects (GET, unauthenticated,
CDN-cacheable). The model is a hierarchy of signed objects, **per tree** = per
`(product, channel)`; the **content tree** (announcements / house-ads) is **axis-separated** from
the update tree so content updates never touch code metadata.

```
embedded root key(s) (current + next)          ← shipped in each client build
  └ root.json                                  ← role delegations + public keys, signed M-of-N
     ├ update tree  (product/channel): current → [snapshot] → targets(=manifest) → artifacts/{sha256}
     └ content tree (product/audience): feed current → [feed-snapshot] → feed-targets(=feed) → feed-body/{sha256} (cohort only)
```

`current` (the timestamp role) is the **only mutable object per tree**; everything else is
immutable and content-addressed (`{sha256}`).

### 3. Objects consumed

| Object | Role | Consumed fields (Suite) |
| --- | --- | --- |
| `root.json` (+ `root/{n}.json`) | root | public keys + role delegations + thresholds (**public material only**); `spec_version` |
| `current` (per tree) | timestamp | `gen`, `targets_sha256`, `snapshot_sha256`, `expires`, `min_client_version`, `poll_after`, `priority`, `history[]`, `successor?` |
| `snapshot/{sha}` | snapshot | consistency anchor (anti mix-and-match); **may be skipped by an aggregating client — see §4** |
| `targets/{sha}` (= manifest) | targets | `latest{version, released_at, artifact_url, artifact_sha256, changelog_url?, requires}`, `min_supported_version`, `channels`, `provenance`; exhaustive over every artifact `sha256` |
| `artifacts/{sha256}` | target body | the binary (verified by hash before apply) |
| feed `current` (per `product/audience/locale`) | timestamp | `gen`, `targets_sha256` → the `feed-targets` object |
| `feed-targets/{sha}` (= the `feed` object) | targets | `kind` (`announcement`\|`ad`), `gen`, `content_sha256`, `count`, `audience`, `locale`; cohort-only |
| `feed-body/{sha256}` | (body) | JSON **array** of `announcement` / `ad` items, verified against `feed-targets.content_sha256` **before** trusting items |

Every object carries `{schema_version, spec_version}`; **no raw keys, no PII, no per-user
identifiers** are ever published. Channel selection stays `?channel={stable|beta|dev}` (default
`stable`, subset of the declared `channels`). Per-product fan-out; cost bounded by `ETag` /
`If-None-Match` → `304`. The bare-array `getAnnouncementsFeed` / `getAdsFeed` of the Topic 1 shape
are **removed**; feeds now follow the content-tree chain (Origin operationIds: `getRoot` /
`getCurrent` / `getSnapshot` / `getTargets` / `getFeedCurrent` / `getFeedTargets` / `getFeedBody`;
`getEntitlementCurrent` / `getEntitlementPolicy` are unused in suite mode — §7).

### 4. Trust, keys & verification order

- **Embedded root, no TOFU.** Suite ships root public keys (**current + next**, for overlap
  rotation) at build time and verifies `root.json` against them with the **root M-of-N threshold**,
  enforcing a **root-version floor** (a withheld rotation fails closed).
- **Primitive unchanged.** Each object's `.jws` sidecar is a **detached JWS** (RFC 7515 + RFC 7797
  unencoded payload, `b64:false`, `crit:["b64"]`), protected header `{alg, kid}` (`EdDSA`/Ed25519
  preferred; `ES256`/`RS256` allowed), covering the **exact served bytes**. Suite verifies **without
  a generic JWS / TUF parser** — it reconstructs the signing input
  `ASCII(BASE64URL(protected) + ".") ‖ <raw body bytes>` and verifies with **libsodium**
  (`sodium_crypto_sign_verify_detached`) or **openssl** (EC/RSA).
- **Per-`.jws` rules:** verify with the **role's** delegated key; `kid`-valid-at-`iat`
  (`kid.not_after ≥ signature iat`); per-major **algorithm allowlist**, asymmetric-only, `"none"`
  forbidden.
- **Verification order (client MUST):**
  1. verify `root.json` (embedded root, M-of-N, version floor) → resolve `role → {keyids, threshold}`;
  2. fetch + verify `current` (timestamp role) → check **`gen` monotonic** (§5) and **`expires`
     freshness** (§5);
  3. `[snapshot]` — the authority always emits it; **an aggregating client may skip it** by pinning
     `current.targets_sha256` (a single-`targets` tree needs no separate snapshot consistency check);
  4. fetch + verify `targets` at `current.targets_sha256` (targets role; exhaustive artifact hashes);
  5. only then **trust fields**; verify **`artifact_sha256`** before applying any downloaded artifact.

### 5. Anti-attack obligations (consumer side)

- **Rollback** — persist a **per-product `gen` watermark** (**product-scoped, cross-channel**, so a
  channel switch cannot downgrade); reject a `current` whose `gen` is below the watermark or the
  **build-time `gen`/date floor**; honor a `min_valid_generation` watermark / `poisoned` marker
  (refused even if cached).
- **Freeze** — `current.expires` (**default 30 d**, per-tree) with **fail-degraded** behaviour
  (`fresh → warn → refuse-new → hard`) mapped to **per-card** dashboard state — one product's stale
  `current` must **not** refuse the whole dashboard. `poll_after` (default 6 h) is honored with
  **client-side jitter**; the aggregator staggers polls per product.
- **Mix-and-match** — the authority always emits a **per-tree** `snapshot`; Suite uses the
  constrained-client **reduction** of §4 step 3. There is **no global snapshot** across products, so
  aggregation stays O(N) per-tree.

### 6. Content tree — feeds & locale fallback

`announcements` / `ads` are reached per `(product, audience, locale)` through the content-tree chain
(fallback **semantics unchanged** from Topic 1; only the object shape moved to the content tree):

1. `feed current` (`/v1/feeds/{product}/{audience}/{locale}/current`) → verify (timestamp role) → read
   `targets_sha256`;
2. `feed-targets/{sha}` (the signed `feed` object) → verify (targets role) → read `kind`,
   `content_sha256`, `count`;
3. `feed-body/{sha256}` → verify the **served bytes against `content_sha256`** → only then trust the
   item array (`announcement` / `ad` items validate against their schemas).

Locale & audience rules (Suite maintains **ja + en** only):

- Requested locale variant unpublished → **404** → Suite refetches the **en** variant (ja/en are
  always published).
- `count = 0` → published-but-empty → show "none" (do **not** fall back).
- `audience` is the cohort `{free, paid}` from the federation IdP claim (§7); house-ad feeds are
  **`free` only** (paid suppresses house-ads client-side).
- The update tree (`targets`) is locale-independent; its 404 means product/channel absent.

### 7. Entitlement — unchanged

In **suite mode**, `tier` / `ads_off` come from the **federation IdP claim**
([ADR 0012](0012-federation-participation-contract.md)), **not** an Origin read endpoint. Paid
suppresses house-ads client-side; house-ads carry `target.tier="free"`. Suite does not read the
Origin entitlement audience-policy / `min_valid_generation` object in suite mode.

### 8. Update determination (Suite-side)

- **Update available** = installed version `<` `targets.latest.version` (semver compare), and the
  candidate's `gen` is **not below** the persisted watermark (§5).
- **Forced update** = installed `<` `targets.min_supported_version` (security floor) → surfaced distinctly.
- **"Update all"** ordering uses `targets.latest.requires` (dependency min-compatible ranges) + the
  catalog DAG (`tools/validate-catalog.sh`), per ADR 0013. `requires` stays in `targets`.
- No PII / customer data ever sent to Origin (signed static GETs only).

## Consequences

**Benefits.** Vetted resistance to rollback / freeze / mix-and-match / key-compromise without
hand-rolled crypto; offline-verifiable; mirror-resistant; cheapest architecture (no read server).
Fixing the model pre-launch avoids a `/v1`→`/v2` migration of an immortal, embedded-client-readable
contract. The transport primitive and SSOT split from Topic 1 are preserved, so prior agreement is
not lost.

**Conformance is the linchpin (consumer cost is bounded by it).** The added work is **client state
and chain logic, not new crypto** (the §4 primitive is unchanged). To keep that cost acceptable,
Suite implements against Origin's **single canonical verifier + signed conformance corpus**
(positive + negative vectors: `gen` rollback, expired, revoked `kid`, missing artifact hash,
snapshot mismatch, poisoned, root-version-floor violation, disallowed alg). The corpus is a **merge
gate for every consumer**; Suite (PHP, sodium/openssl) ships its own verifier and proves parity by
running the corpus in CI.

**Sequencing (D6, agreed, clean pre-launch switch).**
1. **Origin first (blocks Suite client):** publish read schemas/paths (`root.json` / `current`
   incl. `targets_sha256` / `snapshot` / `targets` / feeds) + the **verification-order spec** + the
   **canonical verifier** + the **conformance corpus / signed vectors**.
2. **Suite next:** implement the Origin client against that corpus (CI green); this ADR is the
   target. **The client is intentionally not started before step 1** — building against an undefined
   verifier/vectors would defeat the conformance guarantee.
3. **Origin then:** ratify ADR 0006 `proposed → accepted` and amend ADR 0001 / 0002.
   Production key ceremony and publishing stay **human-gated** (outside this agreement).

**Other Suite follow-up.** The `catalog/apps.json` schema extension for
`icon/description/category/min_suite_version` is a **separate issue** (unrelated to this read-model
change). The dashboard's Origin feeds remain a Phase-B placeholder until the client lands.

## Related

- Origin authority: `nene-origin/docs/adr/0006-signed-static-read-model-profiled-tuf.md` (proposed →
  to be accepted), `…/0001`, `…/0002`, `…/0005`
- ADR 0013 (update aggregation & dependency-ordered upgrade orchestration)
- ADR 0012 (federation participation — entitlement claim source)
- Terminology: `docs/explanation/terminology.md` §4.3 (Origin read-model vocabulary)
- Cross-repo agreement notes: `/home/xi/docker/huddle-suite-and-origin/` (Topic 1: `suite-0001…0003`;
  Topic 2: `suite-0004`/`suite-0005`, `origin-0004…0006`, `HUDDLE END (ack)`)
- Issue: `#207` (original), `#226` (Topic 2 revision)
- Supersedes: the flat read-model shape in this ADR's 2026-06-23 version / Superseded by: none

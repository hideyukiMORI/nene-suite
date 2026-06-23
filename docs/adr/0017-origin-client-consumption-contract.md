# ADR 0017: Origin client consumption contract (Suite ↔ NeNe Origin)

## Status

accepted

## Context

NeNe Suite's dashboard surfaces three Origin-fed signals per installed app — **available
updates**, **announcements**, and **house-ads** — mirroring the Adobe Creative Cloud
launcher. [ADR 0013](0013-update-aggregation-and-upgrade-orchestration.md) established Suite
as the optional **aggregator/upgrade orchestrator** but deferred the concrete wire contract
to "a Suite-side ADR when implementation lands". This ADR records that contract, as agreed
with the NeNe Origin authority.

The agreement was reached 2026-06-23 in a cross-repo working session (notes archived at
`/home/xi/docker/huddle-suite-and-origin/`). Origin's authoritative spec lives in the
`nene-origin` repo (`nene-origin/docs/spec/openapi.yaml` + `*.schema.json`); Origin owns the
contract and is filing the amendments noted under **Origin-side follow-up**. This ADR is the
**consumer** record (owner: nene-suite) so Suite can implement against a stable target.

Layering recap (ADR 0013): Origin is the private signing authority; each product ships a thin
client; Suite optionally aggregates. Suite must never become required for a standalone product
(scope contract).

## Decision

### 1. Source of truth (SSOT)

- **Suite `catalog/apps.json` owns the app roster** and the display metadata
  (`icon` / `description` / `category` / `min_suite_version` / `deprecation`).
- **Origin owns per-product signals only** — version/update, announcements, house-ads.
  There is **no global "all apps" endpoint**; Suite fans out per product slug from its own
  roster. (This keeps the roster a Suite concern and avoids Origin enumerating the portfolio.)

### 2. Read API (consumed by Suite)

Base = `NENE_ORIGIN_URL`, all under `/v1`, signed **static** objects (GET, unauthenticated,
CDN-cacheable). Suite consumes:

| Object | Request | Shape (consumed fields) |
| --- | --- | --- |
| version manifest | `GET /v1/manifest/{product}?channel=` | `latest{version, released_at, artifact_url, artifact_sha256, changelog_url?, requires}`, `min_supported_version`, `channels`. `channel` ∈ {stable,beta,dev}, default `stable`, subset of declared `channels`. |
| announcements | `GET /v1/announcements/{product}?locale=&since=` | signed JSON **array**; `id, severity(info\|important\|security), locale, title, body_md, publish_from, publish_until, target{version_range}, link_url`. |
| house-ads | `GET /v1/ads/{product}?locale=` | signed JSON **array**; `id, locale, title, body_md?, creative_url, link_url, weight, impression_cap, target{version_range?, tier:"free"}`. |
| keys | `GET /v1/keys` | JWKS (public members only); `kid` + rotation `active\|next\|retiring\|retired` + `overlap_until`. |

Per-product fan-out over the roster; cost bounded by `ETag` / `If-None-Match` → `304`.

### 3. Signature verification

Every body has a sibling **`.jws` sidecar** (e.g. `…/manifest/{product}.jws`): a **detached
JWS** (RFC 7515) using **RFC 7797 unencoded payload** (`b64:false`, `crit:["b64"]`), protected
header `{alg, kid}` (`EdDSA`/Ed25519 preferred; `ES256`/`RS256` allowed). The signature covers
the **exact served bytes** (Origin invariant: signed canonical bytes == served bytes; CDN does
not transform bodies).

Suite verifies **without a generic JWS parser** — it reconstructs the RFC 7797 signing input
`ASCII(BASE64URL(protected) + ".") ‖ <raw body bytes>` and verifies directly with **libsodium**
(`sodium_crypto_sign_verify_detached`, Ed25519) or **openssl** (EC/RSA). `firebase/php-jwt`
(used for federation JWTs) is **not** used here — it does not support unencoded/detached payloads.

Client verification order (MUST): fetch `/v1/keys` (pin a root key at build; refresh only on an
unknown `kid` that chains to pinned material) → verify the `.jws` over the raw body → only then
trust fields → verify `artifact_sha256` **before** applying any downloaded update.

### 4. Locale fallback

`announcements`/`ads` are per-locale signed objects. Suite maintains **ja + en** only (other
locales fall back to en — see frontend i18n posture).

- Requested locale unpublished → **404** → Suite refetches **en** (ja/en are always published).
- Published but empty → **200** with a signed `[]` → show "none" (do **not** fall back).
- `manifest` is locale-independent; its 404 means product/channel absent.

### 5. Entitlement

In **suite mode**, `tier` / `ads_off` come from the **federation IdP claim**
([ADR 0012](0012-federation-participation-contract.md)), **not** an Origin read endpoint. Paid
suppresses house-ads client-side; house-ads carry `target.tier="free"`. Suite does not call any
`/v1/entitlement` endpoint.

### 6. Update determination (Suite-side)

- **Update available** = installed version `<` `manifest.latest.version` (semver compare).
- **Forced update** = installed `<` `min_supported_version` (security floor) → surfaced distinctly.
- **"Update all"** ordering uses `latest.requires` (dependency min-compatible ranges) + the
  catalog DAG (`tools/validate-catalog.sh`), per ADR 0013.
- No PII / customer data ever sent to Origin (signed static GETs only).

## Consequences

**Benefits.** A stable, signed, CDN-friendly contract Suite can implement against now; no live
Origin server or auth needed; verification is library-independent (raw sodium/openssl); roster
stays a Suite concern.

**Costs / follow-up.**
- **Origin-side (owned by nene-origin):** ADR 0001 §4 amendment (`.jws` sidecar + RFC 7797);
  `openapi.yaml` adds `?channel=`, the `.jws` sidecars, and the locale-fallback semantics.
- **Suite-side (this repo):** build the Origin client — per-product fetch (ETag) + `.jws`
  verification + version-compare/forced-update + dependency-ordered "update all"; plus the
  catalog-schema extension for `icon/description/category/min_suite_version` (separate issue).
  UI wiring (updates badge, announcements rail, ad slot) follows the frontend IA work.
- Suite depends on Origin shipping the amendment before end-to-end verification against a real
  feed; unit tests use a local test keypair + signed fixtures in the interim.

## Related

- ADR 0013 (update aggregation & dependency-ordered upgrade orchestration — this concretizes its consumption contract)
- ADR 0012 (federation participation — entitlement claim source)
- `docs/explanation/suite-environment-contract.md` (`NENE_ORIGIN_URL`), `docs/explanation/terminology.md` §4
- Origin authority spec: `nene-origin/docs/spec/openapi.yaml`, `nene-origin/docs/adr/0001`, `…/0002`, `…/0005`
- Cross-repo agreement notes: `/home/xi/docker/huddle-suite-and-origin/`
- Issue: `#207`
- Supersedes: none / Superseded by: none

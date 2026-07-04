# Origin client — local dev-key E2E

**Status: how-to (dev only).** Run the suite's Origin consumption client (ADR 0017,
O0–O5b) against a **locally published, dev-key-signed `/v1` tree** — the full
verification path (detached JWS → embedded trust anchor → watermark) with **no
production keys, no production URLs, no network egress**. First verified end to end
on 2026-07-04 (updates / announcements / house-ads all `available: true`).

Prerequisite: `../nene-origin` checked out alongside this repo. Everything below is
inside Origin's guardrails — dev/test keys only, local mirrors only; the real publish
stays a human ceremony (Origin CLAUDE.md / ADR 0008).

**Related:** [ADR 0017](../adr/0017-origin-client-consumption-contract.md),
[`.env.suite.example`](../../.env.suite.example) (Origin section), Origin-side specs
(`nene-origin/docs/spec/signing-cli.md`, `publish.md`).

---

## 1. Sign a dev tree (in `../nene-origin`)

```bash
cd ../nene-origin
php bin/origin-sign keygen --keys signing-keys          # once; gitignored dev keys

# One release spec per product the suite has installed (catalog ids!), e.g.:
#   { "product": "nene-invoice", "channel": "stable", "version": "1.4.0",
#     "released_at": "...", "artifact_url": "https://...", "min_php": "8.2",
#     "min_supported_version": "1.2.0", "channels": ["stable"] }
php bin/origin-sign sign-update --spec release-invoice.json --artifact dummy.zip \
  --out build/dev-e2e/t-invoice --keys signing-keys --state var/signing/generation.json

# Feeds: one {product, audience:free, locale} coordinate per run (ja + en).
# Spec shape: { product, audience, locale, feeds: { announcement: [...], ad: [...] } }
php bin/origin-sign sign-feed --spec feed-nene-invoice-ja.json \
  --out build/dev-e2e/f-invoice-ja --keys signing-keys --state var/signing/generation.json
```

The suite reads `v1/{product}/{channel}/current` per **installed** app
(`GET /api/v1/installed-apps` tells you which products to sign) and
`v1/feeds/{product}/free/{locale}/current` — audience is fixed to `free` for now.

## 2. Publish to local mirrors + serve

```bash
# publish-flip (immutables first → current last → post-flip verify); ≥2 targets
php bin/origin-publish publish --tree build/dev-e2e/t-invoice \
  --target var/dev-mirrors/m1 --target var/dev-mirrors/m2
# repeat per staged tree — trees merge on the mirror (per-product/per-coordinate currents)

# Serve mirror 1 on the suite's compose network (alias origin-mirror) + host :9103
docker run -d --name nene-origin-dev-mirror --network nene-suite_default \
  --network-alias origin-mirror -p 9103:80 \
  -v "$PWD/var/dev-mirrors/m1:/usr/share/nginx/html:ro" nginx:alpine
```

Port 9103 sits in Origin's `91xx` local allocation
(`nene-origin/docs/development/local-ports.md`); never reuse suite ports.

## 3. Build the trust anchor

`trust-anchor.json` is `{root_keyids, root_threshold, keys[]}` — the **root** entries
of the dev `signing-keys/keyring.json`, public JWK members only
(`kid`/`kty`/`use`/`alg`/`crv`/`x`/`status`). No private members (`d`) — the suite's
`OriginTrustAnchor` only consumes public key material, but keep the file clean anyway.

## 4. Wire the suite

```bash
# .env.suite (both lines; remove them to disable again)
NENE_ORIGIN_URL=http://origin-mirror
NENE_ORIGIN_TRUST_ANCHOR_PATH=/tmp/trust-anchor.json

docker compose --env-file .env.suite up -d suite
docker cp ../nene-origin/build/dev-e2e/trust-anchor.json nene-suite-suite-1:/tmp/trust-anchor.json
```

> **The anchor copy is ephemeral.** `/tmp` inside the container survives restarts but
> not recreation (`up -d` after an env/image change) — re-run the `docker cp`. For a
> durable setup mount it instead (compose `volumes:` →
> `/run/secrets/origin-trust-anchor.json:ro`) and point the env var there.

## 5. Verify

```bash
TOKEN=$(curl -s -X POST http://localhost:8800/api/v1/auth/session \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"devpassword12"}' | jq -r .token)
curl -s http://localhost:8800/api/v1/origin/updates -H "Authorization: Bearer $TOKEN"
```

Expected: `available: true` with one signal per installed product
(`status: "unknown"` until the sibling's `/machine/health` version probe is keyed —
that is honest, not a bug), and the dashboard panels (login at `:5188` or `:8800`)
show the signed announcements / house-ads.

Teardown: remove the two `NENE_ORIGIN_*` lines from `.env.suite`, `up -d suite`,
`docker rm -f nene-origin-dev-mirror`.

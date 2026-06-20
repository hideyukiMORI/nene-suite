# Staging deployment (ConoHa VPS)

How NeNe Suite runs on the ConoHa VPS as **staging/demo**. The host follows
`main` (no tags, approvals, or manual gates — those belong to a future
production overlay).

## Topology

A single **VPS-wide Caddy** is the only public entry point. Each app container
serves internal HTTP only and is reached over a shared external Docker network
named `edge`. The suite container listens on **port 80** (Apache, `EXPOSE 80`
in the `Dockerfile`); `8800` is only the local host-side port from
`docker-compose.yml` and is **not** used on the VPS.

```
Internet ──443──> [VPS Caddy] ──edge──> nene-suite-app:80
                                        (other apps: <name>:80 …)

nene-suite stack: nene-suite-app ──default──> db
```

- Caddy: one shared stack for the whole VPS.
- Suite reverse-proxy target: `nene-suite-app:80`.
- The control DB (`db`) joins the project `default` network only — never `edge`,
  and its host port is not published.

## Repository artifact

Only one file in this repository is staging-specific:

- `compose.staging.yaml` — an override applied on top of `docker-compose.yml`.

Local development is unchanged (`docker compose up -d`, apex on `8800`).

## VPS-side artifacts (not in git)

These live on the VPS and are created once by hand:

| Path | Purpose |
| --- | --- |
| `edge` network | `docker network create edge` (shared by Caddy + apps) |
| `/home/deploy/stacks/caddy/compose.yaml` | shared Caddy stack (owns 80/443, joins `edge`) |
| `/home/deploy/stacks/caddy/Caddyfile` | `suite-stg.nene-suite.com { reverse_proxy nene-suite-app:80 }` |
| `/home/deploy/apps/nene-suite/` | repo clone (with `../NENE2` path dependency alongside) |
| `/home/deploy/apps/nene-suite/.env.suite` | secrets, `APP_ENV=production` — VPS only |
| `/home/deploy/apps/nene-suite/deploy-staging.sh` | pull + rebuild + health check |

The `.env.suite` file is never committed (see `.env.suite.example` for the
template).

## First-time setup (once)

```bash
# 1. shared network
docker network create edge

# 2. shared Caddy stack
cd /home/deploy/stacks/caddy
docker compose up -d

# 3. suite app: clone repo + NENE2 path dependency side by side
cd /home/deploy/apps
git clone git@github.com:hideyukiMORI/nene-suite.git
git clone --depth=1 https://github.com/hideyukiMORI/NENE2.git NENE2
cd nene-suite
cp .env.suite.example .env.suite   # then fill strong secrets

# 4. first deploy
docker compose -f docker-compose.yml -f compose.staging.yaml up -d --build
```

## Routine deploy

```bash
cd /home/deploy/apps/nene-suite
git fetch origin main && git reset --hard origin/main
docker compose -f docker-compose.yml -f compose.staging.yaml up -d --build
docker compose -f docker-compose.yml -f compose.staging.yaml ps
curl -fsS https://suite-stg.nene-suite.com/health
```

A future Issue wires GitHub Actions to invoke this over SSH after CI succeeds
on `main`.

## Compose version note

`compose.staging.yaml` uses the `!reset []` override tag, which requires Docker
Compose **v2.24+**. If the VPS Compose is older and rejects it, fall back to a
standalone VPS-only compose file instead of the base + override pair.

## Verification

- Local still works unchanged: `docker compose up -d` (apex on `8800`).
- Merged config is valid:
  `docker compose -f docker-compose.yml -f compose.staging.yaml config`.
- `curl -fsS https://suite-stg.nene-suite.com/health` returns 200.
- The control DB port is not published to the host or exposed externally.

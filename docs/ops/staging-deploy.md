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

## Self-contained image

The `Dockerfile` is multi-stage and self-contained:

1. A `node:22` stage runs `npm ci && npm run build` in `frontend/` to compile the
   React/Vite SPA to static assets. The app is served **same-origin** (it calls
   `/api/v1` and `/health` on its own host), so no `VITE_API_BASE_URL` is needed
   at build time.
2. The `php:8.4-apache` stage clones the NENE2 path dependency **inside the
   build** (into `/var/www/NENE2`, which `composer.json` references as
   `../NENE2`) and runs `composer install`. There is therefore **no need to
   clone NENE2 next to the repo on the VPS**. The built SPA from stage 1 is
   copied into the Apache document root (`public_html/`) alongside `index.php`.

`public_html/.htaccess` ties the two together: `/api/*` and `/health` go to the
PHP front controller (`index.php`), real files (SPA assets, `index.html`,
`openapi.php`) are served directly, and any other path falls back to
`index.html` for client-side routing. Without it every backend route except `/`
returns an Apache 404.

The frontend is built into the image — there is **no separate `npm run build`
step on the VPS**. The Vite dev server (`5188`) is local-only. For reproducible
production builds, pin NENE2 with `--build-arg NENE2_GIT_REF=<tag>`.

## Repository artifacts

Staging-specific files tracked in this repository:

- `compose.staging.yaml` — an override applied on top of `docker-compose.yml`.
- `ops/staging/deploy-staging.sh` — brings the stack up and verifies health
  (build + `/health`). It does **not** pull code; the caller pulls first.
  Override `APP_DIR` / `HEALTH_URL` to reuse it elsewhere.
- `.github/workflows/deploy-staging.yml` — pulls latest on the VPS, then runs
  the script above over SSH.

Local development is unchanged (`docker compose up -d`, apex on `8800`).

## VPS-side artifacts (not in git)

These live on the VPS and are created once by hand:

| Path | Purpose |
| --- | --- |
| `edge` network | `docker network create edge` (shared by Caddy + apps) |
| `/home/deploy/stacks/caddy/compose.yaml` | shared Caddy stack (owns 80/443, joins `edge`) |
| `/home/deploy/stacks/caddy/Caddyfile` | `suite-stg.nene-suite.com { reverse_proxy nene-suite-app:80 }` |
| `/home/deploy/envs/suite-stg/nene-suite/` | repo clone (NENE2 is fetched inside the image — no sibling clone needed) |
| `/home/deploy/envs/suite-stg/nene-suite/.env.suite` | secrets, `APP_ENV=production` — VPS only |

The `.env.suite` file is never committed (see `.env.suite.example` for the
template).

## First-time setup (once)

```bash
# 1. shared network
docker network create edge

# 2. shared Caddy stack
cd /home/deploy/stacks/caddy
docker compose up -d

# 3. suite app (NENE2 is cloned inside the image — no sibling clone needed)
cd /home/deploy/envs/suite-stg
git clone git@github.com:hideyukiMORI/nene-suite.git
cd nene-suite
cp .env.suite.example .env.suite   # then fill strong secrets

# 4. first deploy
docker compose --env-file .env.suite \
  -f docker-compose.yml -f compose.staging.yaml up -d --build
```

`--env-file .env.suite` is required so the top-level `${...}` interpolations
(DB passwords) resolve.

## Routine deploy

Automatic on every `main` push: CI success triggers
`.github/workflows/deploy-staging.yml`, which SSHes in, pulls, and runs the repo
deploy script (`STAGING_SSH_HOST` / `STAGING_SSH_USER` / `STAGING_SSH_KEY`,
optional `STAGING_SSH_PORT`).

To deploy by hand, do the same two steps the workflow does — pull, then run the
repo script:

```bash
cd /home/deploy/envs/suite-stg/nene-suite
git fetch origin main && git reset --hard origin/main
bash ops/staging/deploy-staging.sh
```

## Compose version note

`compose.staging.yaml` uses the `!reset []` override tag, which requires Docker
Compose **v2.24+**. If the VPS Compose is older and rejects it, fall back to a
standalone VPS-only compose file instead of the base + override pair.

## Verification

- Local still works unchanged: `docker compose up -d` (apex on `8800`).
- Merged config is valid:
  `docker compose -f docker-compose.yml -f compose.staging.yaml config`.
- `curl -fsS https://suite-stg.nene-suite.com/health` returns 200 (`{"status":"ok"}`).
- `https://suite-stg.nene-suite.com/` serves the SPA shell (`index.html`); a deep
  link such as `/login` also returns the SPA, not an Apache 404.
- The control DB port is not published to the host or exposed externally.
- The auto-deploy pipeline (CI → `Deploy (staging)`) reaches the VPS and ends with `health OK`.

# Staging deployment

How NeNe Suite is deployed to a staging/demo host. The host follows `main` (no tags, approvals,
or manual gates — those belong to a future production overlay).

> **Host-specific values are not in this repository.** Real hostnames, server paths, and the
> deploy account live in the fleet's non-public infrastructure runbook, which is the source of
> truth for anything you would actually type into a shell on that machine. This page documents
> the **shape** of the deployment — what the pieces are and how they fit — so it stays useful
> without pinning a particular machine. Placeholders below are written as `<angle-bracket>`
> names; substitute them from the runbook.

## Topology

A single **host-wide Caddy** is the only public entry point. Each app container serves internal
HTTP only and is reached over a shared external Docker network named `edge`. The suite container
listens on **port 80** (Apache, `EXPOSE 80` in the `Dockerfile`); `8800` is only the local
host-side port from `docker-compose.yml` and is **not** used on the staging host.

```
Internet ──443──> [Caddy] ──edge──> nene-suite-app:80
                                    (other apps: <name>:80 …)

nene-suite stack: nene-suite-app ──default──> db
```

- Caddy: one shared stack for the whole host.
- Suite reverse-proxy target: `nene-suite-app:80` (the container name is fixed in
  `compose.staging.yaml`, so Caddy can address it without discovery).
- The control DB (`db`) joins the project `default` network only — never `edge`, and its host
  port is not published.

## Self-contained image

The `Dockerfile` is multi-stage and self-contained:

1. A `node:22` stage runs `npm ci && npm run build` in `frontend/` to compile the React/Vite SPA
   to static assets. The app is served **same-origin** (it calls `/api/v1` and `/health` on its
   own host), so no `VITE_API_BASE_URL` is needed at build time.
2. The `php:8.4-apache` stage clones the NENE2 path dependency **inside the build** (into
   `/var/www/NENE2`, which `composer.json` references as `../NENE2`) and runs `composer install`.
   There is therefore **no need to clone NENE2 next to the repo on the host**. The built SPA from
   stage 1 is copied into the Apache document root (`public_html/`) alongside `index.php`.

`public_html/.htaccess` ties the two together: `/api/*` and `/health` go to the PHP front
controller (`index.php`), real files (SPA assets, `index.html`, `openapi.php`) are served
directly, and any other path falls back to `index.html` for client-side routing. Without it every
backend route except `/` returns an Apache 404.

The container entrypoint (`ops/docker/entrypoint.sh`) applies pending database migrations
(`phinx migrate`, idempotent) before Apache starts, so every deploy keeps the control-DB schema
current with no manual migrate step. `phinx` ships in the production image as a `require`
dependency. See [ADR 0014](../adr/0014-schema-migration-lifecycle.md).

The frontend is built into the image — there is **no separate `npm run build` step on the host**.
The Vite dev server (`5188`) is local-only. For reproducible production builds, pin NENE2 with
`--build-arg NENE2_GIT_REF=<tag>`.

## Repository artifacts

Staging-specific files tracked in this repository:

- `compose.staging.yaml` — an override applied on top of `docker-compose.yml`.
- `ops/staging/deploy-staging.sh` — brings the stack up and verifies health (build + `/health`).
  It does **not** pull code; the caller pulls first. Override `APP_DIR` / `HEALTH_URL` to reuse
  it elsewhere.
- `.github/workflows/deploy-staging.yml` — pulls latest on the host, then runs the script above
  over SSH.

Local development is unchanged (`docker compose up -d`, apex on `8800`).

## Host-side artifacts (not in git)

These live on the staging host and are created once by hand. The concrete paths and hostname come
from the infrastructure runbook; the roles below are the stable part.

| Role | Purpose |
| --- | --- |
| `edge` network | `docker network create edge` (shared by Caddy + apps) |
| Caddy stack directory | shared Caddy stack (owns 80/443, joins `edge`) |
| Caddy site block | `<suite-host> { reverse_proxy nene-suite-app:80 }` |
| Suite app directory | repo clone (NENE2 is fetched inside the image — no sibling clone needed) |
| `.env.suite` inside it | secrets, `APP_ENV=production` — host only |

The `.env.suite` file is never committed (see `.env.suite.example` for the template).

## First-time setup (once)

```bash
# 1. shared network
docker network create edge

# 2. shared Caddy stack
cd <caddy-stack-dir>
docker compose up -d

# 3. suite app (NENE2 is cloned inside the image — no sibling clone needed)
cd <apps-parent-dir>
git clone git@github.com:hideyukiMORI/nene-suite.git
cd nene-suite
cp .env.suite.example .env.suite   # then fill strong secrets

# 4. first deploy (entrypoint applies migrations automatically)
docker compose --env-file .env.suite \
  -f docker-compose.yml -f compose.staging.yaml up -d --build

# 5. one-time org bootstrap — first operator, disclaimer, app DBs, manifest.
#    Schema is already migrated by the entrypoint; this seeds the operator etc.
#    Requires the installer env vars (NENE_SUITE_APEX_OPERATOR_*, disclaimer) in
#    .env.suite — see .env.suite.example.
docker compose --env-file .env.suite \
  -f docker-compose.yml -f compose.staging.yaml \
  run --rm suite php installer/install.php
```

`--env-file .env.suite` is required so the top-level `${...}` interpolations (DB passwords)
resolve.

Schema migrations run automatically on every server start (entrypoint — ADR 0014), so routine
deploys need no migrate step. Step 5 (org bootstrap) is run **once** per environment; without it
there is no apex operator to log in with.

## Routine deploy

Automatic on every `main` push: CI success triggers `.github/workflows/deploy-staging.yml`, which
SSHes in, pulls, and runs the repo deploy script (`STAGING_SSH_HOST` / `STAGING_SSH_USER` /
`STAGING_SSH_KEY`, optional `STAGING_SSH_PORT` — values held as repository secrets).

To deploy by hand, do the same two steps the workflow does — pull, then run the repo script:

```bash
cd <suite-app-dir>
git fetch origin main && git reset --hard origin/main
bash ops/staging/deploy-staging.sh
```

## Compose version note

`compose.staging.yaml` uses the `!reset []` override tag, which requires Docker Compose
**v2.24+**. If the host's Compose is older and rejects it, fall back to a standalone host-only
compose file instead of the base + override pair.

## Verification

- Local still works unchanged: `docker compose up -d` (apex on `8800`).
- Merged config is valid:
  `docker compose -f docker-compose.yml -f compose.staging.yaml config`.
- `curl -fsS https://<suite-host>/health` returns 200 (`{"status":"ok"}`).
- `https://<suite-host>/` serves the SPA shell (`index.html`); a deep link such as `/login` also
  returns the SPA, not an Apache 404.
- The control DB port is not published to the host or exposed externally.
- The auto-deploy pipeline (CI → `Deploy (staging)`) reaches the host and ends with `health OK`.

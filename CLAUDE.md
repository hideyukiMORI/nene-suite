# CLAUDE.md — NeNe Suite

Agent entry point: see [`AGENTS.md`](./AGENTS.md) for operating rules, scope contract, and ADR policy.

---

## Local development port assignments

All NeNe apps share a single Docker host. Ports are fixed to avoid collisions.

### NeNe Suite (this repo)

| Service | Local port | Notes |
| --- | --- | --- |
| Apex HTTP | **8800** | `docker compose up suite` |
| MySQL (control DB) | **3390** | exposed for local inspection only |
| Vite dev server | **5188** | `npm run dev` inside `frontend/` |

### Portfolio-wide port registry (do not reuse)

| App | HTTP | DB / Dev |
| --- | --- | --- |
| NENE2 | 82\*\* | 3316 |
| NeNe Clear | 83\*\* | 5173 |
| NeNe Profile | 84\*\* | 3409 |
| NeNe Invoice | 85\*\* | 5185 |
| NeNe Vault | 86\*\* | 5186 |
| NeNe Concierge | 87\*\* | 3790 |
| NeNe Records | 180\*\* | — |
| **NeNe Suite** | **88\*\*** | **3390 / 5188** |

When adding new services to `docker-compose.yml` (e.g., unlocking sibling service stubs),
always use the ports from this table. Never hardcode `80`, `3306`, or other defaults.

---

## Quick start

```bash
cp .env.suite.example .env.suite
# fill required vars (passwords, org name, etc.)
docker compose up db -d
# Install (once): org bootstrap — first operator, disclaimer, app DBs, manifest.
# install.php also applies migrations.
docker compose run --rm suite php installer/install.php
docker compose up -d
# apex shell → http://localhost:8800
```

**Install vs deploy/upgrade.** `installer/install.php` is the one-time
organization bootstrap. Schema migrations are **idempotent and applied
automatically on every server start** by the container entrypoint
(`phinx migrate`), so a plain `docker compose up -d` keeps the schema current on
fresh and upgraded hosts alike. See [ADR 0014](./docs/adr/0014-schema-migration-lifecycle.md).

## Frontend dev server

```bash
cd frontend
npm ci
npm run dev        # Vite on http://localhost:5188
```

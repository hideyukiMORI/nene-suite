# ADR 0014: Schema Migration Lifecycle — Idempotent Migrate on Startup, Separate from Org Bootstrap

## Status

accepted

## Context

NeNe Suite is a **Tier B installer/orchestrator** that operators clone from
GitHub and run on their own host (`docker compose up`). For that to work, a
fresh host must end up with the control-database schema applied.

Two gaps made this fail in practice:

1. **The documented installer was broken in the shipped image.**
   `installer/install.php` shells out to `vendor/bin/phinx migrate`, but
   `robmorgan/phinx` was a `require-dev` dependency. The production image is
   built with `composer install --no-dev`, so phinx was absent and the
   documented `docker compose run --rm suite php installer/install.php` flow
   failed at the migration step.

2. **The deploy path never applied schema.** `ops/staging/deploy-staging.sh`
   only runs `docker compose up -d --build` plus a health check. Nothing in the
   container lifecycle ran migrations, so a freshly deployed `nene_suite`
   database had zero tables. Only the DB-independent endpoints (`/health`,
   `/api/v1/catalog/apps`, which reads `catalog/apps.json`) worked.

The root confusion was treating **schema migration** and **organization
bootstrap** as one "install" step. They have different properties:

- **Schema migration** is idempotent and must run on a fresh install *and* on
  every upgrade. `phinx migrate` only applies migrations not yet recorded in
  `phinxlog`, so running it repeatedly is safe.
- **Organization bootstrap** (first apex operator, disclaimer acceptance, app
  database provisioning, install manifest) is one-time and needs
  human-supplied input (admin credentials, selected apps, disclaimer consent).
  It cannot be fully automated.

## Decision

### 1. The migration runner is a runtime dependency

Move `robmorgan/phinx` from `require-dev` to `require`. The production
(`--no-dev`) image now ships phinx so it can apply migrations at runtime. The
migration tool is part of the product, not just developer tooling.

### 2. Migrations run idempotently on server startup

A container entrypoint (`ops/docker/entrypoint.sh`, wired via `ENTRYPOINT`)
applies pending migrations before serving:

```
wait/retry → vendor/bin/phinx migrate -c phinx.php → exec docker-php-entrypoint apache2-foreground
```

- A fresh host gets all tables; an upgraded host gets only new migrations.
- Auto-deploy (`docker compose up -d --build`) therefore keeps the schema
  current with no extra step — gap (2) closes for free.
- Migration runs **only for the server command** (`CMD = apache2-foreground`).
  One-off invocations such as `docker compose run --rm suite php
  installer/install.php` skip the auto-migrate so unrelated commands do not
  block on the database; `install.php` still runs its own migrate.
- A migration failure on server start is **fatal** (exit non-zero) — fail loud
  rather than serve against an inconsistent schema.

### 3. Organization bootstrap stays an explicit, one-time step

`installer/install.php` remains the bootstrap for first apex operator,
disclaimer acceptance, app-database provisioning, and the install manifest. It
is run once per host and is **not** automated, because it requires operator
input. Its internal `phinx migrate` call is retained and harmless (idempotent).

### 4. Documentation splits the two lifecycles

`README.md`, `CLAUDE.md`, and `docs/ops/staging-deploy.md` describe:

- **Install (once):** bring up the stack, run `installer/install.php` for the
  org bootstrap.
- **Deploy / upgrade (every push):** `docker compose up -d --build`; the
  entrypoint applies pending migrations automatically.

### 5. Non-goals

- No change to per-app (sibling) schema — siblings own their own migrations.
- Suite does not auto-create the first operator or accept the disclaimer on the
  operator's behalf; those stay explicit (disclaimer is a binding consent —
  ADR 0003).

## Consequences

**Benefits.** `docker compose up` is sufficient to reach a current schema on a
fresh or upgraded host; the documented GitHub-clone install path works against
the shipped image; auto-deploy no longer leaves an empty database.

**Costs.** The production image is larger (phinx + its dependencies —
`symfony/console`, `symfony/config`, `cakephp/database`, …, moved into the
production partition of `composer.lock`). Container start is slightly slower
because it waits for the database and runs migrate before serving.

**Risks.** A long-running or failing migration blocks startup; this is
intentional (fail loud) but means migrations must stay fast and backward-safe.
Single-container deployment means no migration/serve race; if Suite ever scales
to multiple replicas, migration must move to a dedicated one-shot job.

## Related

- Issue: `#121`
- ADR 0010 (install manifest persistence), ADR 0011 (control database URL
  resolution — `NENE_SUITE_CONTROL_DATABASE_URL`), ADR 0013 (upgrade
  orchestration — upgrades imply migrations), ADR 0003 (installer disclaimer).
- `installer/install.php`, `ops/docker/entrypoint.sh`, `Dockerfile`, `phinx.php`,
  `database/migrations/`.
- `docs/ops/staging-deploy.md` (install vs deploy/upgrade).
- Supersedes: none. Superseded by: none.

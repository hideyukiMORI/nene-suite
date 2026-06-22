#!/usr/bin/env bash
# NeNe Suite container entrypoint.
#
# On a normal server start (CMD = apache2-foreground), apply pending database
# migrations before serving. `phinx migrate` is idempotent — it only runs
# migrations not yet recorded in `phinxlog` — so this safely covers both a fresh
# install (all tables created) and an upgrade (only new migrations applied).
# See docs/adr/0014-schema-migration-lifecycle.md.
#
# Migration runs ONLY for the server command. One-off invocations
# (e.g. `docker compose run --rm suite php installer/install.php`) skip the
# auto-migrate here; installer/install.php runs its own migrate step. This keeps
# unrelated one-off commands from blocking on the database.
set -euo pipefail

if [ "${1:-apache2-foreground}" = "apache2-foreground" ]; then
  echo "[entrypoint] applying database migrations (phinx)..."
  attempt=0
  max_attempts=30
  until vendor/bin/phinx migrate -c phinx.php; do
    attempt=$((attempt + 1))
    if [ "$attempt" -ge "$max_attempts" ]; then
      echo "[entrypoint] ERROR: migrations failed after ${attempt} attempts." >&2
      echo "[entrypoint] Check NENE_SUITE_CONTROL_DATABASE_URL and that the DB is reachable." >&2
      exit 1
    fi
    echo "[entrypoint] migrate failed (attempt ${attempt}/${max_attempts}) — DB not ready? retrying in 2s..." >&2
    sleep 2
  done
  echo "[entrypoint] migrations OK."

  # Fail closed (milestone A1.5): refuse to serve without a configured apex JWT secret.
  # Serving-only — the one-off installer (CMD `php installer/install.php`) skips this block,
  # so a fresh install before the secret is written is unaffected. `set -e` aborts on failure.
  echo "[entrypoint] verifying apex JWT signing secret (fail-closed)..."
  php ops/docker/preflight-jwt-secret.php

  # Hosted edition only (milestone B1.7): fail closed if the federation signing key is missing or
  # inconsistent with the active published key. Self-skips for OSS (exits 0). Reads the
  # federation_signing_keys table, so it MUST run after `phinx migrate`. `set -e` aborts on a
  # hosted misconfig.
  echo "[entrypoint] verifying federation signing key (hosted edition; fail-closed)..."
  php ops/docker/preflight-federation-key.php

  # Backfill tenancy onto pre-A4 installs (milestone A5): give existing operators their
  # default-org membership before A6 reads one. Idempotent; reads tenancy tables, so it MUST
  # run after `phinx migrate`. Warn-and-continue (the script exits 0 even on failure) so a
  # backfill issue never aborts the boot under `set -e`.
  echo "[entrypoint] backfilling tenancy memberships (idempotent; ADR 0014)..."
  php ops/docker/backfill-tenancy.php
fi

# Hand off to the official PHP image entrypoint so its setup still runs.
exec docker-php-entrypoint "$@"

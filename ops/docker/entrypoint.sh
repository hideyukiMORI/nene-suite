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
fi

# Hand off to the official PHP image entrypoint so its setup still runs.
exec docker-php-entrypoint "$@"

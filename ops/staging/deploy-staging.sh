#!/usr/bin/env bash
# Bring up the staging stack and verify health. This script does NOT pull code —
# the caller (GitHub Actions deploy-staging.yml, or an operator) is responsible
# for `git fetch` / `git reset --hard origin/main` before invoking it. Keeping
# the pull out of this script avoids self-overwrite and first-run bootstrap
# problems. No VPS-specific values are hardcoded; override via APP_DIR / HEALTH_URL.
set -euo pipefail

APP_DIR="${APP_DIR:-/home/deploy/envs/suite-stg/nene-suite}"
HEALTH_URL="${HEALTH_URL:-https://suite-stg.nene-suite.com/health}"
COMPOSE=(docker compose --env-file .env.suite -f docker-compose.yml -f compose.staging.yaml)

cd "$APP_DIR"

"${COMPOSE[@]}" up -d --build
"${COMPOSE[@]}" ps

for _ in $(seq 1 30); do
  if curl -fsS "$HEALTH_URL" >/dev/null; then
    echo "health OK"
    exit 0
  fi
  sleep 2
done

echo "health check FAILED"
"${COMPOSE[@]}" logs --tail=100 suite
exit 1

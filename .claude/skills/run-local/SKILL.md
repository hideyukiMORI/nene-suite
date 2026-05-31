---
description: Start NeNe Suite backend (PHP 8800) and frontend (Vite 5188) for local development
---

# Run NeNe Suite locally

## Prerequisites

- PHP 8.4, Node 22, composer dependencies installed
- `.env` exists at project root (copy from `.env.suite.example` and set DB to SQLite — see below)
- Migrations run: `vendor/bin/phinx migrate -c phinx.php`

## First-time setup

```bash
# 1. Create .env (SQLite for local dev — same pattern as sibling apps)
cp .env.suite.example .env
# Edit .env: set DB_ADAPTER=sqlite, DB_NAME=var/nene_suite.sqlite,
#            APP_ENV=local, NENE2_LOCAL_JWT_SECRET=<any string>

# 2. Run migrations
vendor/bin/phinx migrate -c phinx.php
# NOTE: Phinx creates var/nene_suite.sqlite.sqlite3; rename to match DB_NAME:
mv var/nene_suite.sqlite.sqlite3 var/nene_suite.sqlite

# 3. Create first operator (no HTTP endpoint for bootstrap — use CLI)
php -r "
require 'vendor/autoload.php';
use NeNeSuite\Http\RuntimeContainerFactory;
use NeNeSuite\Auth\CreateOperatorInput;
use NeNeSuite\Auth\CreateOperatorUseCaseInterface;
\$c = (new RuntimeContainerFactory(__DIR__))->create();
\$out = \$c->get(CreateOperatorUseCaseInterface::class)
           ->execute(new CreateOperatorInput('admin@example.com', 'yourpassword12', 'Admin'));
echo 'Created: ' . \$out->operator->email . PHP_EOL;
"
```

## Start backend

```bash
# IMPORTANT: use index.php as the router script (not -t flag).
# -t public_html/ does NOT pass php://input to POST handlers correctly.
php -S 0.0.0.0:8800 public_html/index.php > /tmp/nene-suite-backend.log 2>&1 &
```

Verify: `curl http://localhost:8800/health`

## Start frontend

```bash
cd frontend && npm run dev &
# Vite listens on http://localhost:5188 (fixed in vite.config.ts)
# API proxy: /api/* and /health → http://localhost:8800
```

Verify: `curl -o /dev/null -w "%{http_code}" http://localhost:5188/`

## Stop all

```bash
pkill -f "php -S 0.0.0.0:8800"
pkill -f "vite"
```

## Key URLs

| URL | Description |
|---|---|
| http://localhost:5188/ | Frontend (login → launcher) |
| http://localhost:8800/health | Backend health check |
| http://localhost:8800/api/v1/catalog/apps | App catalog (unauthenticated) |
| http://localhost:8800/api/v1/auth/session | Login (POST) |

## Known quirks

- **PHP built-in server + `-t` flag**: `php -S ... -t public_html/` does not
  correctly pass `php://input` for POST requests. Always use the router-script
  form: `php -S 0.0.0.0:8800 public_html/index.php`.
- **Phinx SQLite naming**: Phinx appends `.sqlite3` to `DB_NAME` when creating
  the file. After first `phinx migrate`, rename
  `var/nene_suite.sqlite.sqlite3` → `var/nene_suite.sqlite` to match
  NENE2's `PdoConnectionFactory` which uses `DB_NAME` literally.

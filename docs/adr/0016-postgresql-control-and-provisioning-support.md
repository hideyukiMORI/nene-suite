# ADR 0016: PostgreSQL support for the control DB and provisioning

## Status

accepted

## Context

NeNe Suite's control database (`nene_suite`) and the Tier B installer's app-database
provisioning have been MySQL-only. Hosting operators have a real (if niche) need to run
the suite on **PostgreSQL** — a database they already operate.

Investigation showed the cost is small and concentrated:

- The inherited framework (NENE2, vendored) is already engine-agnostic:
  `Nene2\Database\PdoConnectionFactory` builds `mysql:` / `pgsql:` / `sqlite:` DSNs by
  adapter, and `PdoDatabaseQueryExecutor` maps **SQLSTATE class 23** to
  `DatabaseConstraintException` (covers MySQL `23000` and PostgreSQL `23505`), so the
  idempotent-insert patterns (`PdoRevokedTokenRepository`, `PdoLoginAttemptRepository`)
  work unchanged.
- All suite repositories use positional placeholders, application-generated ULID primary
  keys (no `lastInsertId`), `TEXT` JSON columns (no engine JSON functions), and plain
  `DELETE ... WHERE` GC. Migrations use Phinx abstractions only (no raw SQL).
- MySQL assumptions were limited to: control URL parsing (scheme/port/charset),
  provisioning DSN + `CREATE DATABASE` DDL, one boolean column read, and `docker-compose.yml`.

ADR 0011 fixed the control URL format as `mysql://…` only. This ADR extends that to
PostgreSQL without breaking existing MySQL deployments.

## Decision

### 1. The control URL scheme selects the engine

`NENE_SUITE_CONTROL_DATABASE_URL` accepts `mysql://` (default) and `pgsql://`
(`postgres://` / `postgresql://` normalize to the `pgsql` adapter).
`ControlDatabaseConfigResolver` normalizes the scheme to a PDO/Phinx adapter, defaults the
port per engine when omitted (MySQL `3306`, PostgreSQL `5432`), and sets `charset`
accordingly (`utf8mb4` for MySQL; `utf8` placeholder for PostgreSQL, which the PDO factory
ignores). Unsupported schemes fail fast with a clear error.

### 2. Provisioning uses the same engine as the control DB

The provisioning connection (`NENE_SUITE_PROVISION_DB_*`) derives its engine from the
control URL scheme — a single source of truth, no separate engine flag. Per-engine details:

- **Port / user defaults:** MySQL `3306` / `root`; PostgreSQL `5432` / `postgres`.
- **DSN:** MySQL connects to the server (no dbname); PostgreSQL must connect to a
  maintenance database first — `NENE_SUITE_PROVISION_DB_NAME` (default `postgres`).
- **`CREATE DATABASE`:** MySQL uses `CREATE DATABASE IF NOT EXISTS \`name\` CHARACTER SET
  utf8mb4 COLLATE utf8mb4_unicode_ci`. PostgreSQL has no `IF NOT EXISTS` and cannot run
  `CREATE DATABASE` inside a transaction, so `PdoDatabaseProvisioner` checks `pg_database`
  then issues `CREATE DATABASE "name" ENCODING 'UTF8' TEMPLATE template0` (autocommit).
  The engine is detected from `PDO::ATTR_DRIVER_NAME`; the existing name validation
  (`^[a-z][a-z0-9_]*$`) is retained.

### 3. Boolean reads are engine-agnostic

PostgreSQL returns `boolean` columns as `'t'/'f'`; a naive `(bool)` cast turns `'f'` into
`true`. The single boolean column (`install_sessions.disclaimer_accepted`) is hydrated via a
normalizer accepting `1/'1'/true/'t'/'true'`. **This corrects a latent bug** that would have
treated a not-accepted disclaimer as accepted on PostgreSQL.

### 4. Local stack: a compose overlay

`compose.postgres.yaml` overrides the `db` service (`postgres:16`, `pg_isready` healthcheck,
port `→5432`) and the `suite` env (pgsql control URL + provisioning vars). The default
`docker-compose.yml` (MySQL) is unchanged:

```
docker compose -f docker-compose.yml -f compose.postgres.yaml --env-file .env.suite up -d
```

### 5. Testing posture

Unit tests stay on SQLite (`DatabaseTestKit::sqlite()`), which exercises the engine-agnostic
code. New unit tests cover URL→adapter/port resolution for both engines and the per-engine
provisioning DDL generation. The PostgreSQL execution path (migrate, login, provisioning) is
verified manually via the compose overlay; a CI PostgreSQL integration job is deferred.

## Consequences

**Benefits.** Operators can run the suite control DB and provisioning on PostgreSQL by
choosing the URL scheme. MySQL/SQLite behavior is unchanged (backward compatible). A latent
boolean-read bug is fixed.

**Costs.** Provisioning carries a small per-engine branch. The compose overlay uses a single
superuser role for control + provisioning for simplicity; production may split roles.

**Out of scope.** Sibling application support for PostgreSQL (each sibling repo's concern;
the suite only creates the database). A CI PostgreSQL job. Schema-per-app provisioning models.

## Related

- ADR 0011 (control database URL resolution — extended here to `pgsql://`)
- ADR 0004 (suite environment contract — `NENE_SUITE_*`)
- ADR 0014 (schema migration lifecycle — Phinx migrations are engine-portable)
- `docs/explanation/suite-environment-contract.md` (control DB + provisioning rows)
- `docs/explanation/terminology.md` §4 (`NENE_SUITE_PROVISION_DB_*`)
- Vendored framework: `Nene2\Database\PdoConnectionFactory`, `Nene2\Database\PdoDatabaseQueryExecutor`
- Issue: `#201`
- Supersedes: none
- Superseded by: none

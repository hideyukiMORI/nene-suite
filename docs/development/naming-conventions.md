# Naming Conventions

Single source of truth for NeNe Suite naming. **PHP, TypeScript, JSON Schema,
OpenAPI, and database identifiers** MUST match this document and
[`terminology.md`](../explanation/terminology.md).

Undocumented exceptions require an ADR. Naming violations **block merge to `main`**.

**Enforcement:** PHPStan 8 + PHP-CS-Fixer + ESLint boundaries + PR review + terminology script.

See also: [`backend-standards.md`](./backend-standards.md),
[`frontend-standards.md`](./frontend-standards.md),
[`schema-conventions.md`](./schema-conventions.md).

---

## PHP — classes and files

### Namespace

```text
NeNeSuite\{Domain}\
```

Example: `NeNeSuite\InstallSession\StartInstallSessionUseCase`

Test namespace: `NeNeSuite\Tests\{Domain}\`

### Class roles

| Role | Pattern | Example |
| --- | --- | --- |
| Domain object | `{Entity}.php` | `InstallSession` |
| Input DTO | `{Verb}{Entity}Input` | `StartInstallSessionInput` |
| Output DTO | `{Verb}{Entity}Output` | `CompleteInstallSessionOutput` |
| Use case IF | `{Verb}{Entity}UseCaseInterface` | `StartInstallSessionUseCaseInterface` |
| Use case | `{Verb}{Entity}UseCase` | `StartInstallSessionUseCase` |
| Handler | `{Verb}{Entity}Handler` | `StartInstallSessionHandler` |
| Repository IF | `{Entity}RepositoryInterface` | `InstallSessionRepositoryInterface` |
| PDO repo | `Pdo{Entity}Repository` | `PdoInstallSessionRepository` |
| Route registrar | `{Entity}RouteRegistrar` | `InstallSessionRouteRegistrar` |
| Service provider | `{Entity}ServiceProvider` | `InstallSessionServiceProvider` |
| Domain exception | `{Entity}NotFoundException` | `InstallSessionNotFoundException` |
| Audit command | `RecordSuiteAuditEventCommand` | readonly DTO for recorder |
| Audit recorder | `SuiteAuditRecorder` / `SuiteAuditRecorderInterface` | — |
| Sanitizer | `{Entity}AuditSanitizer` | `SuiteEnvAuditSanitizer` |

Rules:

- One class per file; filename = class name
- `final readonly class` for DTOs, handlers, providers where applicable
- `declare(strict_types=1);` on every PHP file
- Verbs: `Create`, `Get`, `List`, `Update`, `Delete`, `Start`, `Complete`, `Fail`, `Enable`, `Disable`, `Record`

### Methods and properties

| Kind | Style | Example |
| --- | --- | --- |
| Methods | camelCase | `execute()`, `findById()` |
| Private properties | camelCase | `$installSessions` |
| Constants | UPPER_SNAKE_CASE | `MAX_SELECTED_APPS` |
| Enum cases | PascalCase | `InstallSessionStatus::InProgress` |

Use case interface: **exactly one** public method `execute()`.

### Database

| Kind | Style | Example |
| --- | --- | --- |
| Table | snake_case plural | `suite_audit_events`, `install_sessions` |
| Column | snake_case | `before_json`, `org_external_id`, `install_session_id` |
| Migration file | `YYYYMMDDHHMMSS_snake_description.php` | `20260529000000_create_suite_audit_events_table.php` |
| Migration class | PascalCase | `CreateSuiteAuditEventsTable` |

Column names for audit **MUST** match [`audit-trail.md`](../explanation/audit-trail.md) and [`terminology.md`](../explanation/terminology.md) §9.

---

## TypeScript — files and symbols

### Files

| Kind | Pattern | Example |
| --- | --- | --- |
| Component | `PascalCase.tsx` | `InstallWizard.tsx` |
| Hook | `use-kebab-case.ts` | `use-install-wizard.ts` |
| Utility | `kebab-case.ts` | `format-suite-id.ts` |
| Entity folder | kebab-case | `entities/install-session/` |
| Feature folder | kebab-case | `features/install-wizard/` |

### Symbols

| Kind | Style | Example |
| --- | --- | --- |
| Component | PascalCase | `AppLauncher` |
| Props type | `{Component}Props` | `InstallWizardProps` |
| Hook | `use` + PascalCase | `useInstallSession` |
| Model type | PascalCase noun | `InstallSession` |
| Branded ID | `{Entity}Id` | `InstallSessionId` |
| Query keys export | `{entity}Keys` | `installSessionKeys` |
| Enum | PascalCase name, PascalCase members | `WizardStep.Apps` |

**Named exports only** — no default exports in application code.

---

## JSON Schema and catalog

| Kind | Style | Example |
| --- | --- | --- |
| Catalog app id | `nene-{product}` | `nene-invoice`, `nene-clear` |
| Schema file | kebab-case `.schema.json` | `suite-audit-event.schema.json` |
| JSON property | snake_case | `before_json`, `app_versions`, `org_external_id` |
| `$id` | stable HTTPS URL under `nene-suite.dev` | see `schema-conventions.md` |
| Audit `action` | `{entity}.{verb}` snake segments | `install_session.completed` |
| Audit `entity_type` | snake_case | `install_session`, `app_selection` |
| Audit `source` | snake_case enum | `installer_ui`, `apex_admin` |

Catalog `id` values **MUST** match [`terminology.md`](../explanation/terminology.md) §5.

Env vars **MUST** use `NENE_SUITE_*` prefix — never `SUITE_*`.

---

## OpenAPI and HTTP

| Kind | Style | Example |
| --- | --- | --- |
| Path | `/api/v1/` + kebab-case plural | `/api/v1/install-sessions` |
| Path param | camelCase in OpenAPI | `installSessionId` |
| operationId | camelCase verb + entity | `startInstallSession` |
| Schema name | PascalCase | `InstallSessionResponse` |
| Problem type slug | kebab-case | `install-session-not-found` |

Problem Details `type` URI: `https://nene-suite.dev/problems/{slug}`

---

## Installer CLI

| Kind | Style | Example |
| --- | --- | --- |
| Shell script | kebab-case `.sh` | `provision-databases.sh` |
| PHP CLI entry | kebab-case under `tools/installer/` | `run-install.php` |
| Compose file | `compose.yaml`, `compose.prod.yaml` | — |

CLI commands call use cases — names describe operator intent: `suite:install:start`, not internal class names.

---

## Forbidden patterns

| Forbidden | Use instead |
| --- | --- |
| `InstallSessionDTO`, `*Request`, `*Response` as PHP DTO class names | `{Operation}Input` / `{Operation}Output` |
| `src/controllers/`, `src/usecases/` | Domain folders |
| `audit_logs` (suite context) | `suite_audit_events` |
| `SUITE_DB_URL` | `NENE_SUITE_CONTROL_DATABASE_URL` |
| `install_id` in manifest | `suite_id` |
| Lowercase product display names in docs | `NeNe Invoice` per terminology |
| Hungarian notation, abbreviations (`mgr`, `ctx`, `val`) | Full words |

---

## PR checklist

- [ ] New PHP/TS/JSON symbols appear in this doc or `terminology.md` (same PR)
- [ ] Audit actions registered in `audit-trail.md` §4 before use
- [ ] OpenAPI `operationId` unique and camelCase
- [ ] Migration + schema snapshot names align with table names
- [ ] ESLint boundary rules pass for frontend changes

Last updated: 2026-05-29

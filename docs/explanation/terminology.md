# Terminology Registry — Single Source of Truth

**Status: binding (non-negotiable).** This file is the **single source of truth**
for the canonical spelling and form of every NeNe Suite term, identifier, environment
variable, catalog field, JWT claim, and product name used in this repository.

If an identifier appears anywhere in docs, catalog JSON, installer UI copy (English
repository text), scripts, or code, its spelling **MUST** match this registry
**exactly** — same characters, same case, same separators. There is no "close enough."

See also: [ADR 0006](../adr/0006-terminology-registry-binding.md),
[`../review/terminology.md`](../review/terminology.md).

---

## Authority and absolute rules

1. **Exact match is mandatory.** Any spelling variant or typo of a registered
   term is a defect and **blocks merge** — no acceptable synonyms or abbreviations
   outside this registry (except where this file explicitly lists an allowed short form).
2. **Register before you use.** Introducing a new term or identifier, or renaming
   one, **MUST** update this registry in the **same PR**. Docs or code using an
   unregistered term do not merge.
3. **No silent drift across siblings.** Sibling products (Invoice, Clear, …) have
   their own terminology registries. When referencing their identifiers, use **their**
   canonical spellings — do not invent Suite-specific aliases.
4. **Prose vs identifiers.** English prose may say "suite mode" (lowercase) when
   describing behavior; env vars, JSON keys, and code **MUST** use registered
   identifiers (e.g. `NENE_SUITE_MODE`).
5. **士業 (licensed professionals).** Use exact forms in §8 — do not abbreviate
   税理士 as "税理" or translate inconsistently within one document.

---

## 1. Product names (display)

| Concept | Canonical (English) | Never |
| --- | --- | --- |
| This repository / installer | **NeNe Suite** | Nene Suite, NENE Suite, nene-suite (as product name in prose), NeNe-Suite |
| Meta layer (technical) | **suite orchestrator** | suite orchestration layer (unless sentence needs noun), Suite Orchestrator (title case mid-sentence) |
| Control plane (technical) | **deployment control plane** | control panel, admin hub |
| Login / launcher UI | **apex shell** | Apex Shell (mid-sentence), portal (alone), dashboard (alone) |
| Invoice sibling | **NeNe Invoice** | Nene Invoice, nene invoice |
| Clear sibling | **NeNe Clear** | Nene Clear |
| Records sibling | **NeNe Records** | Nene Records |
| Vault sibling | **NeNe Vault** | Nene Vault |
| Profile sibling | **NeNe Profile** | Nene Profile |
| Corpus sibling | **NeNe Corpus** | Nene Corpus |

Repository slugs (`nene-invoice`) are **not** product display names.

---

## 2. Catalog identifiers (`catalog/apps.json`)

### 2.1 App `id` (exact strings)

| Catalog `id` | `name` field | `path` segment |
| --- | --- | --- |
| `nene-invoice` | NeNe Invoice | `nene-invoice` |
| `nene-clear` | NeNe Clear | `nene-clear` |
| `nene-records` | NeNe Records | `nene-records` |
| `nene-vault` | NeNe Vault | `nene-vault` |
| `nene-profile` | NeNe Profile | `nene-profile` |
| `nene-corpus` | NeNe Corpus | `nene-corpus` |

Pattern: `^nene-[a-z0-9-]+$` — lowercase, hyphen-separated, **`nene-` prefix required**.

Never: `nene_invoice`, `NeNe-Invoice`, `invoice`, `neneInvoice`.

### 2.2 Catalog `status` (exact strings)

| Value | Meaning |
| --- | --- |
| `planned` | Not selectable in installer yet |
| `installable` | Selectable per catalog policy |
| `deprecated` | Listed but must not be newly installed |

Never: `ready`, `available`, `active`, `disabled`.

### 2.3 Catalog `provides` tokens (exact strings)

| Token | Owner catalog `id` |
| --- | --- |
| `billing-api` | `nene-invoice` |
| `reconciliation-api` | `nene-clear` |
| `cms-api` | `nene-records` |
| `archive-api` | `nene-vault` |
| `standard-transaction-export` | `nene-profile` |
| `corpus-api` | `nene-corpus` |

Never: `billing_api`, `invoice-api`, `clear-api`.

### 2.4 Catalog JSON field names

| Field | Canonical | Never |
| --- | --- | --- |
| App list | `apps` | `applications`, `products` |
| Dependency list | `requires` | `dependencies`, `depends_on` |
| Capability list | `provides` | `capabilities`, `exports` |
| Web installer path | `install_entry` | `install_path`, `installer_url` |
| DB config object | `database` | `db` |
| DB env prefix | `database.env_prefix` | `db_prefix` |

---

## 3. Deployment modes

| Concept | Canonical prose | Canonical identifier / value |
| --- | --- | --- |
| Standalone install | **standalone mode** | `NENE_SUITE_MODE` unset or `0` |
| Suite install | **suite mode** | `NENE_SUITE_MODE=1` |
| Dual deployment | **dual mode** | (concept — see scope-contract) |

Never: `SINGLE_MODE`, `multi-tenant suite`, `unified mode`, `SUITE_MODE` (missing `NENE_` prefix).

---

## 4. Environment variables (`NENE_SUITE_*`)

All suite orchestrator variables use prefix **`NENE_SUITE_`** (not `NENE2_SUITE_`, not `SUITE_`).

| Variable | Canonical | Never |
| --- | --- | --- |
| Mode flag | `NENE_SUITE_MODE` | `SUITE_MODE`, `NENE2_SUITE_MODE` |
| Installation id | `NENE_SUITE_ID` | `SUITE_ID`, `INSTALL_ID` |
| Public origin | `NENE_SUITE_BASE_URL` | `BASE_URL`, `NENE_BASE_URL` |
| Launcher URL | `NENE_SUITE_APEX_URL` | `APEX_URL`, `NENE_APEX_URL` |
| JWT issuer base | `NENE_SUITE_ISSUER_URL` | `ISSUER_URL`, `AUTH_URL` |
| Shared HMAC secret | `NENE_SUITE_JWT_SECRET` | `JWT_SECRET`, `NENE_JWT_SECRET` |
| Org federation UUID | `NENE_SUITE_ORG_EXTERNAL_ID` | `ORG_UUID`, `TENANT_ID`, `NENE_ORG_ID` |
| Org display name | `NENE_SUITE_ORG_NAME` | `ORG_NAME`, `COMPANY_NAME` |
| Installed app list | `NENE_SUITE_INSTALLED_APPS` | `INSTALLED_APPS`, `APPS` |

### 4.1 Sibling URL variables (pattern)

**Pattern:** `NENE_SUITE_APP_{SNAKE}_URL`

| Catalog `id` | Canonical variable |
| --- | --- |
| `nene-invoice` | `NENE_SUITE_APP_NENE_INVOICE_URL` |
| `nene-clear` | `NENE_SUITE_APP_NENE_CLEAR_URL` |
| `nene-records` | `NENE_SUITE_APP_NENE_RECORDS_URL` |
| `nene-vault` | `NENE_SUITE_APP_NENE_VAULT_URL` |
| `nene-profile` | `NENE_SUITE_APP_NENE_PROFILE_URL` |
| `nene-corpus` | `NENE_SUITE_APP_NENE_CORPUS_URL` |

`{SNAKE}` = catalog `id` with hyphens replaced by underscores, uppercased
(`nene-invoice` → `NENE_INVOICE`).

Never: `NENE_INVOICE_URL` (missing `NENE_SUITE_APP_` prefix), `NENE_SUITE_INVOICE_URL`.

### 4.2 Sibling JWT secret (written by suite, owned by sibling)

| Variable | Canonical |
| --- | --- |
| Per-app HMAC secret | `NENE2_LOCAL_JWT_SECRET` |

Suite copies `NENE_SUITE_JWT_SECRET` into each app's `NENE2_LOCAL_JWT_SECRET`.
Never rename sibling env keys from this registry — they belong to NENE2 consumer apps.

---

## 5. JWT claims (suite mode)

| Claim | Canonical | Never |
| --- | --- | --- |
| Subject | `sub` | `user`, `email` (as claim name) |
| Role | `role` | `user_role`, `permissions` |
| Local org PK | `org_id` | `organization_id` (in JWT claim name) |
| Federation UUID | `org_external_id` | `external_id`, `org_uuid`, `suite_org_id` |
| Installation id | `suite_id` | `install_id`, `NENE_SUITE_ID` (as claim name) |
| Issued at | `iat` | — |
| Expires | `exp` | — |

---

## 6. Organization federation (cross-repo column)

| Concept | Canonical column / env | Never |
| --- | --- | --- |
| Federation UUID in sibling DB | `organizations.external_id` | `org_uuid`, `suite_external_id`, `externalId` |
| Suite-generated UUID | `NENE_SUITE_ORG_EXTERNAL_ID` | same value as registration number |

**Prohibited labels for `external_id` in any Suite UI or doc:**
法人番号, 適格請求書登録番号, 登録番号, corporate number (when implying tax ID).

---

## 7. System of record (SSOT)

| Concept | Canonical | Never |
| --- | --- | --- |
| Abbreviation | **SSOT** | SSoT, SOR, "source of truth" alone (define on first use) |
| First mention in a doc | **system of record (SSOT)** | system-of-record (hyphen in prose) |
| Invoice billing domain | **billing SSOT** | billing source, invoice master, ledger (for Invoice role) |
| Clear evidence domain | **reconciliation evidence** (Clear SSOT) | Clear ledger, payment ledger |

SSOT labels describe **sibling products**, not NeNe Suite itself. Suite is never
"billing SSOT."

---

## 8. Licensed professionals (士業) — exact forms

| Japanese | English gloss in repo docs | Never |
| --- | --- | --- |
| 税理士 | licensed tax accountant (税理士) | tax advisor alone, CPA (for 税理士), 税理 |
| 公認会計士 | certified public accountant (公認会計士) | accountant alone when specificity matters |
| 弁護士 | lawyer (日本法) | attorney (unless quoting external text) |
| 士業 | licensed professionals (士業) | professionals alone when 士業 is meant |

---

## 9. Suite audit trail (Phase 1+)

| Term | Canonical | Never |
| --- | --- | --- |
| Audit store table | **`suite_audit_events`** | `audit_logs`, `audit_log` (suite context) |
| Control database | **`nene_suite` control database** | shared app DB, domain DB |
| Before snapshot column | **`before_json`** | `old_value`, `previous` |
| After snapshot column | **`after_json`** | `new_value`, `next` |
| Action naming | **`{entity}.{verb}`** | free-form strings, `ACTION_*` enums without docs |
| Recorder abstraction | **`SuiteAuditRecorder`** | `AuditLogger`, ad-hoc `file_put_contents` |
| Control DB env var | **`NENE_SUITE_CONTROL_DATABASE_URL`** | `SUITE_DB_URL`, `AUDIT_DATABASE_URL` |
| Install grouping key | **`install_session_id`** | `session_id` (alone), `wizard_id` |
| Redacted placeholder | **`"[REDACTED]"`** | empty string, `***`, `null` for known secret keys |

Event types and `entity_type` values are registered in
[`audit-trail.md`](./audit-trail.md) §4. Do not add actions in code without
updating that section and [`schema/suite-audit-event.schema.json`](../../schema/suite-audit-event.schema.json).

---

## 10. Install manifest fields (Phase 1+)

| Field | Canonical | Never |
| --- | --- | --- |
| Manifest file | **install manifest** | install log, setup.json (unregistered) |
| Suite installation id | `suite_id` | `install_id` in manifest |
| Install timestamp | `installed_at` | `created_at`, `setup_at` |
| Catalog app versions | `app_versions` | `versions` (alone) |
| Federation UUID | `org_external_id` | `external_id` (in manifest — use full name) |
| Enabled integrations | `enabled_integrations` | `integrations`, `wiring` |

| Control DB URL env | `NENE_SUITE_CONTROL_DATABASE_URL` | `SUITE_DB_URL`, `AUDIT_DATABASE_URL` |

Manifest field names are registered when the installer ADR lands; until then,
do not invent manifest keys in code without updating this section.

---

## 11. Tier labels

| Label | Canonical | Never |
| --- | --- | --- |
| Shared hosting path | **Tier A** | tier-a, Tier-A, T1 |
| Docker / VPS path | **Tier B** | tier-b, Tier-B, T2 |

---

## 12. Forbidden marketing phrases (exact strings to avoid)

These strings **MUST NOT** appear in repository docs or installer English copy:

- `compliant out of the box`
- `audit-ready`
- `税理士不要`
- `guaranteed correct`
- `certified by`
- `unified ledger`
- `unified books`

Permitted alternatives: see [`disclaimer.md`](./disclaimer.md) and
[`installer-disclaimer-copy.md`](./installer-disclaimer-copy.md).

---

## 13. Code identifiers (Phase 1+)

| Symbol | Canonical | Never |
| --- | --- | --- |
| PHP root namespace | **`NeNeSuite\`** | `NeneSuite\`, `NeNe\Suite\`, `Suite\` |
| Audit recorder class | **`SuiteAuditRecorder`** | `AuditLogger`, `AuditService` |
| Problem Details base | **`https://nene-suite.dev/problems/`** | ad-hoc problem URLs |
| API prefix | **`/api/v1/`** | `/api/`, unversioned public JSON |
| Locale storage key | **`nene-suite-locale`** | `nene-locale`, `locale` |
| Message key prefix | **`suite.{feature}.{element}`** or **`common.{element}`** | free-form strings in JSX |

Full placement and naming: [`../development/naming-conventions.md`](../development/naming-conventions.md).
Message catalogs: [`../development/i18n.md`](../development/i18n.md).

---

## 14. How to change this registry

1. Open an Issue explaining the new term or correction.
2. Update this file in the same PR as the first use.
3. Run [`../review/terminology.md`](../review/terminology.md) checklist.
4. If the change affects compliance posture, add ADR + professional sign-off when required.

---

Last updated: 2026-05-29

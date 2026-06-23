# Front-end Information Architecture & UI Element Brief

**Audience:** designers (incl. ClaudeDesign) and front-end implementers.
**Purpose:** a single, grounded inventory of the NeNe Suite apex shell — its information
architecture, screens, components, states, flows, and edition/role gating — so design can
start from what actually exists and what is agreed, not from guesswork.

This is a **brief**, not a spec: it says *what* surfaces exist and *why*, tags each by
readiness, and points at the backing API/ADR. Visual design, exact layout, and copy are the
designer's job.

## Product framing

NeNe Suite is the **apex shell** for a portfolio of sibling apps — an **Adobe Creative
Cloud-style launcher + management console**. After login the operator sees a dashboard where
apps can be **opened, installed, updated, and managed**, plus the org/member/audit/settings
surfaces around them. Suite is an orchestrator, never a domain app (see `AGENTS.md`, ADR 0002).

Two axes shape every screen:

- **Edition** (`NENE_SUITE_EDITION`): `oss` (self-hosted, effectively single-org) vs `hosted`
  (vendor multi-org). Multi-org chrome (org switcher, org management) only appears in `hosted`.
- **Role**: platform `superadmin` vs org-scoped `admin` / `member` / `viewer`. The shell shows
  fewer surfaces for lower roles.

## Readiness legend

| Tag | Meaning |
| --- | --- |
| ✅ **implemented** | Exists today in the front-end **and** backend |
| 🟦 **origin-fed** | Data comes from NeNe Origin static JSON (ADR 0017); needs the Suite Origin client (gated on Origin publishing its spec amendment) |
| 🟧 **needs-backend** | Needs new Suite backend work (API/state) |
| 🟨 **needs-design** | UI/UX to be designed; backing data may already exist |

---

## 1. Global shell & navigation

The persistent chrome around every screen.

| Element | Readiness | Notes / backing |
| --- | --- | --- |
| App header (brand, primary nav) | ✅ | nav: Home / Install / Audit / Organizations (role-gated) / Logout |
| **Org switcher + active-org indicator** | ✅ (hosted) | `GET /auth/session/organizations`, `PUT /auth/session/active-organization`; hide in `oss` |
| **Account menu** (profile, security, logout) | 🟨 / partial | logout ✅ (`DELETE /auth/session`); profile/security surfaces are new (§7) |
| **Notifications / activity** (updates available, install/upgrade progress, key-expiry, failures) | 🟨 + 🟦/🟧 | aggregates origin updates + local job state; no surface today |
| **Global search / command palette** | 🟨 | nice-to-have DX; jump to app/settings |
| **Help / About / legal (disclaimer, version)** | 🟨 | disclaimer is binding (ADR 0003); surface suite version + links |
| Locale switcher (ja/en) | ✅ | i18n maintained for ja+en only; other locales fall back to en |

---

## 2. Dashboard / Home (the three pillars + feeds)

The CC-style landing surface. Today it is a flat launcher list; design should evolve it into
a visual, card-based dashboard with three app "pillars" plus the Origin-fed rails.

| Element | Readiness | Notes / backing |
| --- | --- | --- |
| **Installed apps** (open / manage) | ✅ | `GET /installed-apps`; today shows name + SSOT-role + open link |
| **Installable apps** (browse + get) — see §6 | ✅ data / 🟨 surface | `GET /catalog/apps`; today only inside the install wizard, not a browse surface |
| **Updatable apps** (badge, per-app, update-all) | 🟦 | compare installed version × `manifest.latest.version`; `< min_supported_version` ⇒ forced (ADR 0017 / 0013) |
| **Announcements / "What's new" rail** | 🟦 | `GET /v1/announcements/{product}`; severity info/important/security; dismissible |
| **House-ads slot** | 🟦 | `GET /v1/ads/{product}`; weight/impression-cap rotation; suppressed when `ads_off` (federation claim) |
| Empty / first-run state ("no apps → browse catalog") | 🟨 | onboarding for a fresh install |

---

## 3. App surfaces — card, detail, states, actions

**App state model** (a card must render each):
`planned` · `installable` · `installed-active` · `installed-disabled (frozen)` ·
`update-available` · `deprecated`.

| Element | Readiness | Notes / backing |
| --- | --- | --- |
| App **card** (icon, name, category, status, primary action) | 🟨 | icon/description/category are Suite-catalog fields (ADR 0017 SSOT; catalog schema extension pending) |
| App **detail panel/drawer** (description, version, changelog/what's-new, actions) | 🟨 / 🟦 | version+changelog origin-fed; description/icon/category Suite-catalog |
| **Install** flow (wizard) | ✅ | `/install`: app-selection → disclaimer → review → complete (`install-sessions` API). Note loose ends in §11 |
| **Update** action (per-app / update-all, dependency-ordered) | 🟦 | ordering via `latest.requires` + catalog DAG (ADR 0013) |
| **Uninstall = soft-disable (freeze)** | 🟧 | interim policy: freeze data, no login, remove shortcut; sibling login → redirect to Suite (sibling/federation responsibility). Hard-delete is deferred/undecided |
| **Reconfigure / repair** an installed app | 🟨 / 🟧 | not yet defined |
| SSOT-role badge (billing / reconciliation-evidence / cms / archive) | ✅ | already shown in the launcher |

---

## 4. Catalog browse ("store")

Promote "installable apps" out of the wizard into a first-class browsable surface (like the
CC "All apps"): list/grid of catalog apps with description, category, dependencies, and a
per-app **Get/Install** entry that hands off to the install wizard.

| Element | Readiness | Notes / backing |
| --- | --- | --- |
| Catalog list/grid (filter by category/status) | 🟨 | `GET /catalog/apps` exists; browse surface is new |
| Dependency display (`requires` DAG) | ✅ data / 🟨 surface | catalog carries `requires`; wizard already resolves deps |

---

## 5. Management (admin surfaces)

| Element | Readiness | Notes / backing |
| --- | --- | --- |
| **Organizations** console (list/create/rename/disable) | ✅ (superadmin, hosted) | `/organizations` API; `/admin/organizations` |
| **Memberships** (list/grant/role-change/revoke) | ✅ (admin+) | `/organizations/{id}/memberships`, `/memberships/{id}` |
| **Operators** (list/invite/create) | ✅ API / 🟨 surface | `GET/POST /operators` (superadmin); no dedicated screen yet |
| **Audit log** (list) | ✅ | `GET /suite-audit-events`; paginated table |
| Audit **filters** (entity/actor/date/org) + **export** | 🟧 / 🟨 | `export` nav key exists but unimplemented; before/after diff viewer |
| **Federation keys** status dashboard (active/retiring/retired) + rotate/revoke | 🟧 | today CLI/ops only (`docs/ops/federation-key-management.md`); read-only status first |

---

## 6. Account & security (self-service — currently missing)

Distinct from "managing others". The logged-in operator managing **themselves**.

| Element | Readiness | Notes / backing |
| --- | --- | --- |
| Profile (display name, email) | 🟧 | no self-service API/screen yet |
| Change password | 🟧 | no API yet |
| Active sessions / log out everywhere | 🟧 / partial | per-session logout ✅ (token revoke); list/revoke-all is new |

---

## 7. Settings area (not one page — a section)

| Element | Readiness | Notes / backing |
| --- | --- | --- |
| Organization profile (name, slug) | ✅ partial | rename exists; broader profile new |
| Suite settings (disclaimer version, Origin poll cadence, edition info, update channel) | 🟧 | `settings` nav key exists but no route |
| Federation (see §5) | 🟧 | |
| Billing / plan (hosted free + ads model) | 🟨 future | placeholder; tier/ads_off from federation claim (ADR 0012) |
| Integrations / sibling wiring | 🟨 | |

---

## 8. Cross-cutting

- **Edition × role gating** (which surfaces show):

  | Surface | superadmin | admin | member | viewer | oss |
  | --- | --- | --- | --- | --- | --- |
  | Launcher / open apps | ✅ | ✅ | ✅ | ✅ | ✅ |
  | Install / update apps | ✅ | ✅ | — | — | ✅ |
  | Org switcher / org mgmt | ✅ | — | — | — | hidden |
  | Memberships | ✅ | ✅ | — | — | n/a |
  | Operators / federation keys | ✅ | — | — | — | n/a |
  | Audit log | ✅ | ✅ | — | — | ✅ |

  (Role gating is enforced server-side today; the shell should mirror it. Exact member/viewer
  rules for install/audit to be confirmed in design.)

- **Async states**: every data surface needs loading / empty / error states (an `AsyncStates`
  primitive already exists). Install/upgrade need **progress** and **failure** UI (§11).
- **Onboarding / first-run**: fresh install has no operator UI today (CLI bootstrap); hosted
  self-signup is future.
- **i18n**: ja + en only (others fall back to en).
- **Accessibility & responsive**: design baseline (current UI is semantic HTML, minimal CSS).
- **Disclaimer / legal**: binding, no business/legal warranty (ADR 0003) — must be present.

---

## 9. UI element inventory (rollup by readiness)

- ✅ **implemented**: login, launcher (basic), org switcher, install wizard, organizations,
  memberships, audit list, logout, locale switcher.
- 🟦 **origin-fed** (gated on Origin client, ADR 0017): updates badge / per-app update /
  update-all, announcements rail, house-ads slot, version + changelog in app detail.
- 🟧 **needs-backend**: soft-disable (uninstall), account profile/password, sessions list,
  audit filters/export, federation key dashboard, suite settings persistence, operators screen.
- 🟨 **needs-design** (data/most-backing exists): dashboard card layout, app detail panel,
  catalog browse surface, account menu, notifications/activity, settings IA, search,
  help/about, onboarding/empty states.

## 10. Known loose ends in the current front-end

- Install **ReviewStep** shows app IDs, not names.
- Install **failure** UI is missing (`failureCode` exists; `useFailInstallSession` unused).
- `settings` nav key exists but has no route.
- Audit `export` nav key exists but is unimplemented.

## 11. Sequencing note (for implementers, not designers)

- Design can mock 🟦 origin-fed surfaces now; their data depends on the Suite Origin client,
  which is sequenced **after** NeNe Origin publishes its spec amendment (ADR 0017).
- 🟨 needs-design surfaces backed by existing APIs (catalog browse, audit filters UI,
  operators screen) can be built independently.

## References

- Current front-end: `frontend/src/pages/`, `frontend/src/features/` (app-launcher, install-wizard,
  organization-console, membership-console, audit-viewer, active-org-indicator, sign-in)
- API: `docs/openapi/openapi.yaml`
- ADRs: 0017 (Origin consumption), 0013 (update aggregation/upgrade), 0012 (federation/entitlement),
  0015 (hosted multi-tenant), 0007 (audit trail), 0003 (disclaimer), 0002 (orchestrator-not-app)
- Roles: `src/Tenancy/Role.php`; catalog: `catalog/apps.json`
- Schema reference: `docs/reference/schema.md`

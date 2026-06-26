# Current TODO

**Status (2026-06-26).** Phase 1 (Tier B installer MVP) ✅ · Multi-tenant **Phase A**
(A0–A8b) ✅ · **Phase B / B1** (federation IdP key plane + OSS auth hardening) ✅ ·
**Origin consumption client** (O0–O5b, epic #230) ✅. Control DB + provisioning are
PostgreSQL-capable (ADR 0016); the Origin consumption contract (ADR 0017) is now
implemented end to end — detached-JWS verification with conformance-corpus parity, a
per-product `gen` watermark, three read APIs, and dashboard wiring. The **O6 upgrade
orchestration prerequisites are all landed** (installed-version tracking, catalog version mirror,
ADR 0013 acceptance, and the upgrade-orchestration contract — **ADR 0019**, which supersedes the
mis-specified ADR 0018; see below). The **federated user lifecycle** contract — prompt
deprovisioning beyond JIT-on-login (pull lifecycle delta feed + best-effort back-channel logout) —
is now **accepted as ADR 0020** (extends ADR 0012, OQ1–5 resolved; a B2 follow-on, no terminology
change). The **app database topology** contract is **accepted as ADR 0021** — a per-app database
target (`provision` | `adopt` + configurable server); the default reproduces today's behavior, the
MVP adds external-server **adopt** (register an existing DB, no DDL) as the data-plane companion to
ADR 0012 §8, with the §3 one-DB-per-app invariant held. **Next: B2** — sibling-side org resolution +
authorization-code assertion flow (cross-repo)
— and, for O6, the Suite **deployment-driven** orchestrator (Tier B compose: dependency-ordered
image recreate + min-version gating; the sibling migrates on boot) + apex "update all" UI (epic #251).

The Phase A / B1 build-out is tracked in
[`docs/milestones/2026-06-multi-tenant-suite.md`](../milestones/2026-06-multi-tenant-suite.md)
and the [2026-06-22 handover](../handover/2026-06-22-multi-tenant-phase-a.md); the Origin
client is recorded in [`docs/daily-reports/2026-06-25.md`](../daily-reports/2026-06-25.md).
`main`'s git log is the authoritative shipped record. Gate state: PHPUnit **431** /
vitest **68**, all green.

---

**Phase 0–1 — Governance, product design, and installer MVP** (historical log)

## Done

- [x] Issue #1: Governance bootstrap — PR #2
- [x] Issue #3: Installer disclaimer — PR #4
- [x] Issue #5–#6: Review gate + env contract — PR #7
- [x] Issue #8: Orchestration compliance — PR #9
- [x] Issue #10: Terminology registry — PR #11
- [x] Issue #12: Audit trail (before/after) — merged
- [x] Issue #14: NENE2 coding standards — merged
- [x] Issue #16: i18n message catalogs — merged (PR #17)
- [x] Issue #18: Phase 1 OpenAPI contract — merged (PR #19)
- [x] Issue #20: Catalog validation script — merged (PR #21)
- [x] Issue #22: CI workflow (terminology + catalog + OpenAPI lint + frontend) — merged (PR #23)
- [x] Issue #24: Untrack frontend/node_modules + .gitignore fix — merged (PR #25)
- [x] Issue #26: Backend scaffold + first slice (AppCatalog read) — merged (PR #27)
- [x] Issue #28: Backend slice 2 — control DB + SuiteAudit recorder + InstallSession start/get — merged (PR #29)
- [x] Issue #30: Backend slice 3 — app-selection with dependency resolution — merged (PR #31)
- [x] Issue #32: Problem Details base for framework errors → `nene-suite.dev` (NENE2 v1.5.328 / NENE2#1355) — merged (PR #33)
- [x] Issue #34: Backend slice 4 — install-session disclaimer-acceptance + fail — merged (PR #35)
- [x] Issue #36: Backend slice 5 — completeInstallSession + InstallManifest (ADR 0010) — merged (PR #37)
- [x] Issue #38: Backend slice 6 — OpenAPI contract validation (`composer openapi`) + route↔spec test — merged (PR #39)
- [x] Issue #40: OpenAPI contract — apex auth session — merged (PR #41)
- [x] Issue #42: Backend slice 7 — apex Auth domain (operator + JWT session) — merged (PR #43)
- [x] Issue #44: Backend slice 8 — suite-audit-events read (R-08, paginated, authenticated) — merged (PR #45)
- [x] Issue #46: Backend slice 9 — SuiteEnv URL reader + installed-apps launcher (R-06) — merged (PR #47)
- [x] Issue #48: Backend slice 10 — populate install manifest apps[] (SuiteEnv URLs + DatabaseProvision names) — merged (PR #49)
- [x] Issue #50: Frontend slice 1 — strict toolchain + shared/api + auth login vertical — merged (PR #51)
- [x] Issue #52: Frontend slice 2 — installed-app entity + app-launcher feature (apex home) — merged (PR #53)
- [x] Issue #54: Frontend slice 3 — install-session + catalog-app entities + install wizard — merged (PR #55)
- [x] Issue #56: Frontend slice 4 — suite-audit-event entity + audit viewer (admin) — merged (PR #57)
- [x] Issue #58: Frontend slice 5 — shared/ui primitives (AsyncStates/PageHeader) + locale switcher — merged (PR #59)
- [x] Issue #60: `composer openapi` を CI backend job に追加 — PR #62
- [x] Issue #61: Docs 相対リンクチェックツール + CI 追加 — PR #63
- [x] Issue #65: SuiteEnv 生成 + DatabaseProvision write side use case — PR #66
- [x] Issue #67: CreateOperator use case (初回 apex operator 作成) — PR #68
- [x] Issue #69: WriteEnvConfig / ProvisionAppDatabases use case をサービスプロバイダーに配線 — PR #70
- [x] Issue #71: frontend api-types → schema.gen.ts generated types に置き換え — PR #72
- [x] Issue #73: ADR 0011 — NENE_SUITE_CONTROL_DATABASE_URL 解決方針 — PR #74
- [x] Issue #100: Staging compose + operations docs for ConoHa VPS — PR #101
- [x] Issue #103: Docker build/runtime fixes for staging (`NENE2` build clone, Composer failure hard-stop, front-controller rewrite) — PR #104
- [x] Issue #105: Align staging operations docs with the actual VPS layout — PR #106
- [x] Issue #102: Manual ConoHa VPS staging runtime build-out — completed 2026-06-20
- [x] Issue #107: Automatic GitHub Actions deploy to staging after successful `main` CI — PR #108
- [x] Issue #109: Automatic staging deploy smoke test — PR #110

### Frontend: all Phase 1 API surfaces have UI ✅ — login · launcher (installed-apps) · install wizard · audit viewer, with shared/ui primitives + locale switching. Strict layering enforced by ESLint boundaries; every feature has a Vitest+MSW test.

## Phase 1 OpenAPI: all 13 operations implemented ✅

health · catalog · install-session (start/get/app-selection/disclaimer/complete/fail) · auth session (create/get/delete) · suite-audit-events · installed-apps. Every mutation has before/after audit; all Problem `type` use `nene-suite.dev`; `composer openapi` + route↔spec test guard the contract.

## Next (Phase 1 → Phase 2)

- [x] Move staging deploy script into repository-managed `ops/staging/` while keeping VPS-specific values out of git — `ops/staging/deploy-staging.sh` (bootstrap-safe split: pull in workflow, build+health in script) — Issue #111, PR #112.
- [x] **税理士 / 公認会計士 sign-off** — orchestration-compliance §2–§5, ADR 0005 — 辻村総合会計事務所 / 2026-05-31 (Issue #75, PR #76)
- [x] **弁護士 sign-off** — disclaimer.md + installer-disclaimer-copy.md — 西村法律事務所 / 2026-05-31 (Issue #77, PR #78)
- [x] **Tier B installer** (Docker Compose MVP) — InstallerUseCase + installer/install.php + Dockerfile + docker-compose.yml — PR #80
- [x] `ControlDatabaseConfigResolver` 実装 — ADR 0011 follow-up; `phinx.php` と `RuntimeServiceProvider` を更新 — PR #81
- [x] README status / TODO 整合 — PR #116
- [x] `main` ブランチ保護 ruleset `protect-main`（PR必須・CI必須・admin bypass なし）+ `docs/ops/branch-protection.md` — PR #118
- [x] フロントエンドをイメージに同梱（Dockerfile マルチステージ + `.htaccess` SPA ルーティング）— PR #120
- [x] 起動時 entrypoint で冪等 migrate（ADR 0014; phinx を require へ; `phinx.php` の生URLバグ修正）— PR #122
- [x] ADR 0014（schema migration lifecycle）— PR #122
- [x] ADR 0015（Suite hosted multi-tenant mode / 製品エディション / 自己ホスト移行可ポジショニング）draft — PR #124, #126
- [x] Operator provisioning HTTP endpoint — `POST /api/v1/operators` + `ProvisionApexOperatorUseCase` (default-org `Admin` membership) — A4.5, PR #154
- [ ] Shared apex auth middleware — **deferred by design.** Per-handler default-deny via `BearerTokenAuthenticator` / `SuperadminGuard` (first line of `handle()`) is the deliberate pattern while few endpoints need auth (handover §4.3). Revisit only if the authenticated-endpoint count grows.
- [ ] `IntegrationWiring` — Phase 2 scope
- [ ] **App database topology**（ADR 0021 accepted, OQ1–5 確定）— per-app database target（`mode=provision|adopt` ＋ server）。実装: ① env 拡張 `NENE_SUITE_APP_{SNAKE}_DB_*`（OQ1）② manifest `apps[]` に `mode`/`server` 追加・省略可（OQ4）③ provisioner を per-app target へ一般化 ④ adopt（register-only・DDL/DML なし・非破壊）パス＋safety test。MVP は外部サーバ **adopt-only**（外部 provision は defer, OQ2）・単一エンジン維持（OQ5）・adopt 入口は ADR 0012 §8 self-registration と統合（OQ3）。識別子は各実装 PR で terminology 登録（ADR 0018 precedent）。
- [x] `app_versions` の version mirror — **catalog version mirror** が landed（#260, ADR 0013 §4 read-model: `installedVersion`/`availableVersion`）。installed は sibling `/machine/health` 由来（#256/#258）、version-compare は `OriginUpdateAggregator`。manifest への静的 pin はしない（live mirror）。

## Next (hosted edition / NeNe Cloud Free — ADR 0015 draft)

Done (Phase A + B1 — `main`):

- [x] Suite 多org化: `operators` → `organizations` + `memberships` + role；`superadmin` platform console + active-org switcher — Phase A (PR #142–#168, #172, #174)
- [x] セッション JWT に `org_external_id` + role を載せる（pre-A6 token は fallback で失効させない）— A6, PR #158
- [x] Federation IdP key plane — ES256 assertion issuer/verifier・signing-key store/gen・JWKS endpoint・fail-closed preflight・key rotation/revoke（edition-gated）— B1, PR #178–#194
- [x] OSS auth hardening — `NENE_SUITE_EDITION` flag + OSS firewall・login rate-limit・apex logout 失効 — B1, PR #178–#184
- [x] Origin 消費契約 — 署名済 static GET + detached JWS 検証・update/announcements/house-ads のワイヤ契約を ADR 0017 として確定（NeNe Invoice の export/import ADR 0017 とは別物）— PR #208

Remaining (B2–B6 — see milestone §3):

- [ ] アプリの org 解決（`subdomain` / `custom_domain`）+ authorization-code assertion flow を Suite から driving（B2 — cross-repo）
- [ ] **Federated user lifecycle**（B2 follow-on）— JIT-on-login を超える即時 deprovisioning。pull lifecycle delta feed（SCIM 形・§5 roster-pull の user 粒度拡張）＋ best-effort back-channel logout（OIDC 形・login と同じ JWKS 検証）。suite 側の disable/role-revoke/delete を member tool へ伝播。契約は **ADR 0020 accepted**（ADR 0012 を extend・cross-DB 書き込みなし・NENE2 へは generic framework 機能としてのみ依頼）。前提: B1 keys（landed）＋ B2 org 解決＋ ADR 0012 §5 roster-pull surface。実装は別作業。
- [x] Suite Origin **消費**クライアント実装 — profiled-TUF read model：detached-JWS（EdDSA）検証＋conformance corpus parity（15/15, `nene-origin@d5882cf` pin）・per-product `gen` watermark・update/announcements/house-ads read API＋dashboard 配線（O0–O5b, epic #230 closed; PR #232–#250）。trust anchor 未設定時は disabled-degrade。
- [ ] アップグレード **orchestration** — dependency-ordered "update all"。**deployment-driven**（Tier B：Suite が依存順に新イメージ recreate＋min-version gating＋audit、各 sibling は起動時 migrate＝Tier A）。**Suite はデプロイ駆動・apply 実体は sibling の boot migrate**（Origin ADR 0001 §5 / ADR 0013 §3/§8 / ADR 0014 / **ADR 0019**）。backlog epic #251；**前提①②③④ landed**（installed-version 追跡 #256/#258・catalog version mirror #260・ADR 0013 accepted #262・upgrade orchestration ADR 0019（ADR 0018 を supersede））→ 次は Suite のデプロイ駆動 orchestrator＋apex "update all" UI（sibling runtime endpoint は不要・NENE2#1416 取り下げ）。
- [ ] catalog schema 拡張（`icon` / `description` / `category` / `min_suite_version`）+ フロント IA 配線（updates badge / announcements rail / ad slot）
- [ ] entitlement / quota + house-ads 配線（B4 — ADR 0013; suite mode の `tier` は federation IdP claim 由来）
- [ ] 組織まるごと export → 自己ホスト import（B5 — 移行可 headline の launch 前提; 現状 CSV のみ）
- [ ] ADR 0015 の open questions 解消（signup/不正対策、org解決方式、terminology 登録）→ ADR を accepted へ（B6 terminal gate）
- [ ] **launch 前にまとめて** 法務再レビュー（西村法律事務所 — データ受託化）（B6）

## Blockers

- ~~External installer MVP blocked until professional sign-off records merged.~~ **🟢 Resolved 2026-05-31 — both sign-offs on record.**
- Sibling apps need `NENE_SUITE_MODE` env readers (cross-repo Issues).
- Tier A suite wizard depends on sibling release ZIP installers (Invoice Phase 3).

## Handoff

Private meta repo. Compliance model mirrors nene-invoice `accounting-compliance.md`.
Binding trio: scope-contract + orchestration-compliance + disclaimer.

### VPS staging status — 2026-06-20

- ConoHa VPS is active for `suite-stg.nene-suite.com`.
- Shared Caddy stack lives at `/home/deploy/stacks/caddy/` and is attached to the
  external Docker network `edge`.
- Suite staging lives at `/home/deploy/envs/suite-stg/nene-suite/`.
- `.env.suite` is VPS-local and must not be committed.
- `suite-stg.nene-suite.com` routes through Caddy to `nene-suite-app:80`.
- Suite database is internal only; it is not attached to `edge` and has no
  published host ports.
- `https://suite-stg.nene-suite.com/health` returns HTTP 200.
- GitHub Actions staging deploy is active: `main` CI success triggers SSH deploy
  to the VPS and reaches `health OK`. The deploy logic is repository-managed
  (`ops/staging/deploy-staging.sh`); the workflow pulls, then runs that script.
- Detailed daily report: `docs/daily-reports/2026-06-20.md`.

### 2026-06-21

- Frontend now built into the image; staging serves the SPA at `/` (PR #120).
- Schema migrations apply automatically on container start (ADR 0014, PR #122);
  the staging `nene_suite` control DB has its tables.
- `main` is protected by ruleset `protect-main` (PR #118).
- Product direction set: self-hosted OSS + hosted **NeNe Cloud Free**
  (ADR 0015, draft). Anti-lock-in / data portability is the headline.
- phpMyAdmin runs as a VPS-local, out-of-repo compose project (SSH-tunnel only).
- Detailed daily report: `docs/daily-reports/2026-06-21.md`.

### 2026-06-22

- **Multi-tenant Phase A complete (A0–A8b)** — `operators` → organizations /
  memberships / roles, superadmin org + membership consoles, active-org switcher,
  session JWT carries `org_external_id` + role (PR #142–#168 + polish #172/#174).
  OSS single-org behavior preserved (ADR 0015 §8); lockout firewall (A4/A4.5/A5)
  landed before the first runtime change (A6). Record: `docs/handover/2026-06-22-multi-tenant-phase-a.md`.
- **Phase B / B1 complete** — federation IdP key plane + OSS auth hardening
  (PR #178–#194): `NENE_SUITE_EDITION` flag, OSS-flags-off firewall, persistent
  login rate-limit, apex logout-revocation, ES256 assertion issuer/verifier,
  `federation_signing_keys` store + key-gen command, `/.well-known/jwks.json`,
  hosted fail-closed preflight, time-driven rotation + emergency revoke. Apex
  session stays HS256; everything asymmetric is edition-gated (clean OSS build
  holds no key material). Runbook: `docs/ops/federation-key-management.md`.

### 2026-06-23 / 24

- **PostgreSQL support** for the control DB + provisioning (ADR 0016, PR #201–#202).
- **Schema doc generation** — `docs/reference/schema.md` is generated from
  `database/schema` (`composer schema:docs`), with lint, ER diagram, and
  domain grouping; CI freshness check (PR #203–#206).
- **ADR 0017 — Origin consumption contract accepted** (PR #207–#208): signed static
  GETs + RFC 7797 detached-JWS verification for update / announcements / house-ads;
  roster stays a Suite concern (`catalog/apps.json`), Origin owns per-product signals.
- **Frontend IA / UI element brief** (PR #209–#210) — apex shell surfaces + readiness
  (`docs/design/frontend-information-architecture.md`).
- Gate state verified green: PHPUnit **345** / vitest **38**.

### 2026-06-25

- **Origin consumption client complete (O0–O5b, epic #230 closed)** — profiled-TUF read
  model end to end: outbound HTTP seam + `ext-sodium` (O0); detached-JWS (EdDSA) verify
  primitive with an algorithm allowlist, no `none`, kid-valid-at-`iat` (O1a); chain
  verifier with **conformance-corpus parity (15/15)** ported from Origin's reference
  verifier and pinned at `nene-origin@d5882cf` (O1b); per-product `gen` watermark in the
  control DB (monotonic anti-rollback, O2); update aggregation + announcement/house-ad
  feeds with locale fallback (O3/O3b); `GET /api/v1/origin/{updates,announcements,house-ads}`
  read APIs (operator-authenticated, read-only, disabled-degrade when Origin is
  unconfigured, O4); dashboard updates KPI/panel + announcement panel + house-ad slot +
  app-detail change-history (O5/O5b). Updates are surfaced **latest-only with
  `status: unknown`** until installed versions are tracked; every surface keeps an honest
  placeholder when Origin is unconfigured (never fabricated data).
- **O6 re-framed → backlog #251** — the apply is each sibling's own Tier A (ADR 0013
  §3/§8 / Origin ADR 0001 §5); Suite orchestrates ordering / gating / relay only. The
  mis-framing ("Suite downloads + applies") was caught and corrected before any
  scope-violating code. The prerequisite chain starts at the non-destructive
  **installed-version tracking** (via the sibling auth-gated `/machine/health`).
- **O6 prerequisites ①②③④ landed (same day)** — ① installed-version tracking: the sibling
  auth-gated `/machine/health` `version` (NENE2 v1.5.330 / NENE2#1414) — seam + control-DB cache
  (#256), then the `X-NENE2-API-Key` probe + per-app machine-key env (#258); ② **catalog version
  mirror** (ADR 0013 §4 read-model on the catalog API, #260); ③ **ADR 0013 accepted** (#262);
  ④ upgrade-orchestration contract — first **ADR 0018** (#266→#268), then **corrected by ADR 0019**
  (#271): ADR 0018 put the apply on a sibling runtime endpoint (`POST /machine/update`); review found
  a running app cannot redeploy itself, so ADR 0019 makes it **deployment-driven** (Suite recreates
  the sibling container at the new image in dependency order with min-version gating + halt-don't-
  unwind + audit; the sibling migrates on boot — Tier A / ADR 0014). No sibling runtime endpoint —
  **NENE2#1416 withdrawn**; `/machine/health` version (NENE2#1414) is all NENE2 needs.
  Sibling adoption tracked at nene-invoice#496 / nene-clear#182 / nene-records#586. Until a sibling
  reports its version the diff stays `unknown` (defensive). Next: Suite deployment-driven orchestrator
  + apex UI (O6, #251).
- Production activation stays human-gated (root-key ceremony + `NENE_ORIGIN_URL` /
  `NENE_ORIGIN_TRUST_ANCHOR_PATH`); once configured, the placeholders become live data.
- Gate state verified green: PHPUnit **431** / vitest **68**.

### 2026-06-26

- **O6 upgrade contract corrected — ADR 0018 superseded by ADR 0019 (deployment-driven).** Review
  found ADR 0018's apply mechanism (a sibling runtime `POST /machine/update` self-apply) wrong — a
  running app cannot redeploy itself. **ADR 0019** (proposed) makes the apply **deployment-driven**:
  Suite (Tier B compose owner) recreates the sibling container at the new image in dependency order
  with min-version gating + halt-don't-unwind + audit; the sibling migrates on boot (its own Tier A,
  ADR 0014). No sibling runtime endpoint — **NENE2#1416 withdrawn**. (#266/#268 ADR 0018
  proposed→accepted, then #272 supersede.) ADR 0019 open questions — deploy-control mechanism
  (Docker socket vs host-side deploy agent), image provenance, Tier A coexistence — are the next gate.
- **NENE2 framework-health audit — Suite-agnostic confirmed.** NENE2 has zero code/config coupling to
  Suite (one-directional Suite→NENE2). The only blemish (generic "suite/orchestrator" prose in the
  #1414 PHPDoc/CHANGELOG, seeded by our issue wording) was fixed on the NENE2 side (NENE2#1417/#1418).
  Principle recorded: request only generic framework features from NENE2; never name/allude to Suite.
- Session handover: [`docs/handover/2026-06-26-origin-and-o6-prerequisites.md`](../handover/2026-06-26-origin-and-o6-prerequisites.md).
- Gate state: PHPUnit **431** / vitest **68**, all green.

Last updated: 2026-06-26

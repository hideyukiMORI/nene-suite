# Current TODO

**Status (2026-06-27).** Phase 1 (Tier B installer MVP) ✅ · Multi-tenant **Phase A**
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
`main`'s git log is the authoritative shipped record. Gate state: PHPUnit **468** /
vitest **85**, all green. **ADR 0022 mode A** shipped and **ADR 0023 accepted** (post-install DB
re-adoption / sibling preflight). Repo posture: the repository is **public** and professional (士業)
review is **advisory** — consolidated before a public release, not a per-change gate (ADR 0003 / 0005
amended 2026-06-27, #320).

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
- [x] **App database topology**（ADR 0021 accepted, OQ1–5 確定）— per-app database target（`mode=provision|adopt` ＋ server）。**実装① env 駆動 target ＋ adopt 対応エンジン（#280）**・**実装② manifest `apps[]` に `mode`/`server` 記録＋CompleteInstallSession を resolver 配線（#284）** 完了＝**機能的に完結**。既定（provision・suite サーバ）は現行と byte 一致、adopt は register-only（DDL/DML なし）、MVP は外部サーバ **adopt-only**（外部 provision は defer, OQ2）・単一エンジン維持（OQ5）。**adopt 入口は ADR 0022（App Onboarding Modes, accepted）で契約化** — mode A（suite-driven install adopt・今すぐ実装可）と mode B（standalone-first inbound join・B2 依存）を 1 onboarding モデルの 2 entry mode として固定し、database target を install-session 専用にしない（A→B rework 回避）。**mode A PR1（backend）landed（#292）** — install-session が per-app target override（`AppDatabaseTargetSelection`）を carry・layered resolver `SessionDatabaseTargetResolver`（session→env→default・検証は `DatabaseTargetFactory` 共有）・専用オペ `PUT /install-sessions/{id}/database-targets`（`setDatabaseTargets`）＋audit `database_targets.configured`＋`database_targets_json` 永続化。既定（provision）は behavior-preserving。**mode A PR2（frontend, #296）landed＝mode A 完結** — install wizard に `database` step（app ごとに provision/adopt、adopt は server/name）を追加し `setDatabaseTargets` を呼ぶ。mode B（§7/§8・identity 突合）は B2 後（OQ2–4）。
- [ ] **Post-install database re-adoption**（ADR 0023 accepted, OQ1–5 確定）— install 後に app の database target を変更する surface ＋「候補 DB が app にとって正当か」の判断モデル。**判断は app の自己診断（sibling preflight 契約 `POST /machine/database/preflight`・generic・read-only・Suite 非名指し）**、Suite は orchestration ＋ 記録に徹する（一方向依存・app-agnostic 維持）。creds は wire 非掲載・候補 server は env allowlist（SSRF 封じ）・`adoption_token`＋fingerprint で TOCTOU・register-only/可逆（ADR 0021 §3）・反映は deployment-driven（ADR 0019）。OQ 確定: 候補 env `NENE_SUITE_APP_{SNAKE}_DB_CANDIDATE_*`／token は per-app HMAC（machine key）／tenant は `org_external_id`（OSS 単一は n/a）／未対応 app は refuse-by-default＋監査付き override／**read-only 先行**（Admin「Databases」read 面＋preflight、live 再 adopt の write は後続スライス）。実装は NENE2 generic preflight ＋ Suite op/UI の2スライス。**①NENE2 generic preflight: A+B 実装着地**（#1419 A→PR#1422 ／ #1420 B→PR#1423）、**C(#1421 fingerprint+token)は OPEN・defer**（消費者の apply が未定義のため。apply は **deployment-driven**＝アプリの runtime endpoint 不可・deployer 再起動＋**boot 再検証**、ADR 0019/NENE2#1416 整合。NENE2#1421 に返信済）。as-shipped 契約: `POST /machine/database/preflight`・verdict は `recommendation: safe|needs_migration|**needs_review**|refuse`（4値）・`app_identity`/`tenant` は文字列状態・marker 不在は fail-closed しない（`identity_unverified`）。**read 消費側（slice①）は A+B でいま結線可能**（C/token 不要）。Suite 非名指し。**各 sibling は preflight を opt-in 採用が必要**（inspector 配線＋identity marker＋候補プロファイル・未採用は 404 → OQ4 `unknown`→refuse）: 採用 issue **nene-invoice#497 / nene-clear#183 / nene-records#648** 起票済（過去の installed-version 採用 #496/#182/#586 と同流儀）。
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

Public meta repo. Compliance model mirrors nene-invoice `accounting-compliance.md`.
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
- **Federation lifecycle ＋ DB topology session** — **ADR 0020**（federated user lifecycle）/ **ADR 0021**（app database topology）/ **ADR 0022**（app onboarding modes）accepted；ADR 0021 を impl ①②（#280/#284）で機能完結。続けて **ADR 0022 mode A 完結（PR1 backend #292 ＋ PR2 frontend #296）**：install-session が per-app database target override を carry し layered resolver（session→env→default）＋専用オペ `setDatabaseTargets` で受け、install wizard に `database` step を追加。Handover: [`docs/handover/2026-06-26-federation-lifecycle-and-db-topology.md`](../handover/2026-06-26-federation-lifecycle-and-db-topology.md).
- **Mode A 実装セッションの実績＋再開 handover**: [`docs/handover/2026-06-26-mode-a-implementation.md`](../handover/2026-06-26-mode-a-implementation.md) — 4 PR・コードに今あるもの（backend / frontend）・key decisions・プロセス教訓（codegen freshness / frontend `npm run check` / .env caveat / review-before-merge）・次の入口（§5 deferred は B2 ブロック、unblocked は epic #251）。
- Gate state: PHPUnit **468** / vitest **72**, all green.

### 2026-06-27

- **ADR 0022 mode A 完結＋ClaudeDesign 適用**: mode A backend/frontend（#292/#296）の後、admin
  コンソール ＋ install database step に ClaudeDesign 返却デザインを適用（#302・presentation-only）。
- **ADR 0023 — Post-Install Database Re-Adoption ＋ Sibling Preflight Contract** accepted（OQ1–5 確定）:
  install 後の DB target 変更 ＋「候補 DB が正当か」を **app の自己診断（sibling preflight）** で判断し、
  Suite は orchestration ＋ 記録に徹する（app-agnostic）。
- **NENE2 generic preflight の cross-repo build-out**: 起票（NENE2#1419・Suite 非名指し）→ A/B/C 3分割
  → **A+B 着地**（#1422/#1423）→ as-shipped 契約反映。`recommendation` に `needs_review` 追加・marker 不在は
  fail-closed しない。**C(#1421) は apply と対で defer**（apply は deployment-driven＝boot 再検証、ADR 0019/
  NENE2#1416 整合）。sibling 採用: nene-invoice#497 / nene-clear#183 / nene-records#648。
- **Governance — public repo ＋ 士業レビュー advisory 化（#319/#320）**: suite が当初から public である
  事実を反映（private 誤記5箇所訂正）し、士業レビューを binding ゲート → **advisory（public リリース前に
  まとめて推奨・per-change sign-off 廃止）** へ in-place amend（ADR 0003 / 0005 / orchestration-compliance /
  requirements / disclaimer）。MIT AS-IS 無保証・§2–§7 工学 MUST ルール・2026-05-31 sign-off 記録は不変。
- **Docs 鮮度パス＋ルール化（#321）**: README / roadmap / current / milestones を 2026-06-27 状態へ鮮度
  更新し、**日報作成時にドキュメント鮮度を更新する**ことを `workflow.md` / `AGENTS.md` に binding ルール
  として明記。
- **アプリ内ヘルプ基盤（#323・ADR 0024）**: 用語集・各画面の使い方・チュートリアル＋難所の
  インライン注釈（InfoHint）を追加。`../nene-origin`（feat(help) #143–#147）のパターンを参照し、
  nene-suite のデザインシステム（CSS Modules/oklch）・en+ja UI 枠に合わせて移植。**日本語先行**
  （ヘルプ本文は構造化TS・ADR 0009 への scoped 例外）、ホバー型 tooltip 非依存の a11y 設計。
  `/help`・`/help/glossary`・`/help/:slug` ＋ apex ナビ、install wizard に HelpLink/InfoHint 配線。
  PR1＝基盤＋install wizard（残り画面・英語本文は後続）。
- **membership console 修正（#325）**: ロール変更/削除の 409（last-admin invariant 等）が画面に
  出ていなかった問題を修正。hook から `changeErrorKey`/`revokeErrorKey` を公開しコンソールに表示、
  さらに**最後の admin** は降格オプション/削除を先回りで無効化＋InfoHint で理由提示（バックエンドは
  正しく据え置き）。en+ja・vitest 追加。
- **10-persona UX 評価＋クイックウィン（epic #327 / A #328）**: login〜install〜org/membership〜
  help〜i18n を10ペルソナで正常系ウォークスルー評価（全員 read-only）。findings を epic #327 ＋
  A(#328)/B(#329–#334) に記録。**A（クイックウィン5件）実装**: 免責/review の Markdown `**` 除去・
  DatabaseStep「?」の i18n 化・依存ヒントの向き修正＋friendly name・組織無効化に inline 確認・
  作成/付与 form の `reset()` を onSuccess へ。B（high 構造課題）は順次。
- Detailed daily report: [`docs/daily-reports/2026-06-27.md`](../daily-reports/2026-06-27.md).
- Gate state: PHPUnit **468** / vitest **85**, all green.

Last updated: 2026-06-27

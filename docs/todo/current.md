# Current TODO

**Status (2026-07-02).** Phase 1 (Tier B installer MVP) ✅ · Multi-tenant **Phase A**
(A0–A8b) ✅ · **Phase B / B1** (federation IdP key plane + OSS auth hardening) ✅ ·
**Origin consumption client** (O0–O5b, epic #230) ✅. Control DB + provisioning are
PostgreSQL-capable (ADR 0016); the Origin consumption contract (ADR 0017) is now
implemented end to end — detached-JWS verification with conformance-corpus parity, a
per-product `gen` watermark, three read APIs, and dashboard wiring. The **O6 upgrade
orchestration prerequisites are all landed** (installed-version tracking, catalog version mirror,
ADR 0013 acceptance, and the upgrade-orchestration contract — **ADR 0019**, which supersedes the
mis-specified ADR 0018 and is now **accepted** (2026-07-02 amendment: OQ1 = host-side deploy agent
behind an opt-in capability flag, OQ2 = staged image provenance, OQ3 deferred); see below). The **federated user lifecycle** contract — prompt
deprovisioning beyond JIT-on-login (pull lifecycle delta feed + best-effort back-channel logout) —
is now **accepted as ADR 0020** (extends ADR 0012, OQ1–5 resolved; a B2 follow-on, no terminology
change). The **MFA / step-up** contract (**ADR 0025**) has its NENE2 side **shipped** — the generic
TOTP primitive + recovery codes (NENE2#1427) landed in **NENE2 v1.5.333** (2026-07-02 verified);
the Suite-side IdP enforce + step-up claim remains future work. The **app database topology**
contract is **accepted as ADR 0021** — a per-app database
target (`provision` | `adopt` + configurable server); the default reproduces today's behavior, the
MVP adds external-server **adopt** (register an existing DB, no DDL) as the data-plane companion to
ADR 0012 §8, with the §3 one-DB-per-app invariant held. **Next: B2** — sibling-side org resolution +
authorization-code assertion flow (cross-repo)
— and, for O6, the Suite **deployment-driven** orchestrator (Tier B compose: dependency-ordered
image recreate + min-version gating; the sibling migrates on boot) + apex "update all" UI (epic #251).

The Phase A / B1 build-out is tracked in
[`docs/milestones/2026-06-multi-tenant-suite.md`](../milestones/2026-06-multi-tenant-suite.md)
and the [2026-06-22 handover](../handover/2026-06-22-multi-tenant-phase-a.md); the Origin
client is recorded in [`docs/daily/2026-06-25.md`](../daily/2026-06-25.md).
`main`'s git log is the authoritative shipped record. Gate state: PHPUnit **513** /
vitest **138**, all green (measured 2026-07-18; `composer check` now includes the
conformance linter). **ADR 0022 mode A** shipped and **ADR 0023 accepted** (post-install DB
re-adoption / sibling preflight). A **2026-06-27/28 UX-remediation + ClaudeDesign-integration arc**
layered on top: **in-app help** (ADR 0024), the audit viewer's **before/after diff detail + evidence
CSV**, a **responsive left-sidebar shell** (closes B3 #331), home/install polish, and the **MFA /
step-up** decision (**ADR 0025** — generic TOTP in NENE2). **Near-term TODO: finish the persona-eval B
group** (#332 a11y ✅ · #330 help-i18n ✅ · #333 org-reversibility ✅ · #334 locale ✅ — **B group
complete**) and the **#327 medium sweep ✅** (PR #354–#357; server-side org-list pagination is the one
explicit defer). **Next: B2 / O6 (#251)** — see the
[2026-07-02 daily report](../daily/2026-07-02.md).
Repo posture: the repository is **public** and professional (士業)
review is **advisory** — consolidated before a public release, not a per-change gate (ADR 0003 / 0005
amended 2026-06-27, #320). Latest session handover (state + challenges + ordered TODO):
[`../handover/2026-07-03-ux-remediation-adr0019-o6-ready.md`](../handover/2026-07-03-ux-remediation-adr0019-o6-ready.md).

**2026-07-18 (fleet conformance campaign, suite slice).** The **conformance linter is now a
`composer check` gate** (#386/#387 — the 2026-07-07 fleet wave had skipped suite; baseline froze
34 findings, shrink-only). **A1 hooks→model landed** via the fleet codemod (#388/#389 — 18 moves,
FSD `model/` segment). **D1 remediated** (#390/#391): `JwtSecretResolver` now delegates to
`Nene2\Auth\GuardedJwtSecretResolver` (production ignores the dev-secret opt-in and hard-fails;
baseline shrunk 33→32). W1 stage1 (token vocabulary) was verified already complete since PR #381;
the remaining W1 item after `@hideyukimori/nene2-tokens` publishes is a small
codemod-no-op-proof + script-retirement PR. Ops follow-up (fleet-level, hub ledger): audit
`APP_ENV=production` on real deployment env files. Daily reports moved to `docs/daily/`
(fleet convention, #392).

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
- [x] Issue #100: Staging compose + operations docs for the staging VPS — PR #101
- [x] Issue #103: Docker build/runtime fixes for staging (`NENE2` build clone, Composer failure hard-stop, front-controller rewrite) — PR #104
- [x] Issue #105: Align staging operations docs with the actual VPS layout — PR #106
- [x] Issue #102: Manual staging VPS runtime build-out — completed 2026-06-20
- [x] Issue #107: Automatic GitHub Actions deploy to staging after successful `main` CI — PR #108
- [x] Issue #109: Automatic staging deploy smoke test — PR #110

### Frontend: all Phase 1 API surfaces have UI ✅ — login · launcher (installed-apps) · install wizard · audit viewer, with shared/ui primitives + locale switching. Strict layering enforced by ESLint boundaries; every feature has a Vitest+MSW test.

## Phase 1 OpenAPI: all 13 operations implemented ✅

health · catalog · install-session (start/get/app-selection/disclaimer/complete/fail) · auth session (create/get/delete) · suite-audit-events · installed-apps. Every mutation has before/after audit; all Problem `type` use `nene-suite.dev`; `composer openapi` + route↔spec test guard the contract.

## Next (Phase 1 → Phase 2)

- [x] Move staging deploy script into repository-managed `ops/staging/` while keeping VPS-specific values out of git — `ops/staging/deploy-staging.sh` (bootstrap-safe split: pull in workflow, build+health in script) — Issue #111, PR #112.
- [x] **税理士 / 公認会計士 sign-off** — orchestration-compliance §2–§5, ADR 0005 — 辻村総合会計事務所 / 2026-05-31 (Issue #75, PR #76)
- [x] **弁護士 sign-off** — disclaimer.md + installer-disclaimer-copy.md — 外部法務 / 2026-05-31 (Issue #77, PR #78)
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
- [ ] **Post-install database re-adoption**（ADR 0023 accepted, OQ1–5 確定）— install 後に app の database target を変更する surface ＋「候補 DB が app にとって正当か」の判断モデル。**判断は app の自己診断（sibling preflight 契約 `POST /machine/database/preflight`・generic・read-only・Suite 非名指し）**、Suite は orchestration ＋ 記録に徹する（一方向依存・app-agnostic 維持）。creds は wire 非掲載・候補 server は env allowlist（SSRF 封じ）・`adoption_token`＋fingerprint で TOCTOU・register-only/可逆（ADR 0021 §3）・反映は deployment-driven（ADR 0019）。OQ 確定: 候補 env `NENE_SUITE_APP_{SNAKE}_DB_CANDIDATE_*`／token は per-app HMAC（machine key）／tenant は `org_external_id`（OSS 単一は n/a）／未対応 app は refuse-by-default＋監査付き override／**read-only 先行**（Admin「Databases」read 面＋preflight、live 再 adopt の write は後続スライス）。実装は NENE2 generic preflight ＋ Suite op/UI の2スライス。**①NENE2 generic preflight: A+B 実装着地**（#1419 A→PR#1422 ／ #1420 B→PR#1423）、**C(#1421 fingerprint+token)は OPEN・defer**（消費者の apply が未定義のため。apply は **deployment-driven**＝アプリの runtime endpoint 不可・deployer 再起動＋**boot 再検証**、ADR 0019/NENE2#1416 整合。NENE2#1421 に返信済）。as-shipped 契約: `POST /machine/database/preflight`・verdict は `recommendation: safe|needs_migration|**needs_review**|refuse`（4値）・`app_identity`/`tenant` は文字列状態・marker 不在は fail-closed しない（`identity_unverified`）。**read 消費側（slice①）は A+B でいま結線可能**（C/token 不要）。Suite 非名指し。**各 sibling は preflight を opt-in 採用が必要**（inspector 配線＋identity marker＋候補プロファイル・未採用は 404 → OQ4 `unknown`→refuse）: 採用 issue **nene-invoice#497 / nene-clear#183 / nene-records#648** 起票済（過去の installed-version 採用 #496/#182/#586 と同流儀）。**nene-clear は採用完了（clear#182/#183 → clear PR#240/#241・2026-07-03）**: `/machine/health` version・preflight（inspector＋`phinxlog` ledger＋identity marker migration＋env 候補 allowlist）とも稼働可能。invoice / records は未採用のまま。
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
- [ ] **launch 前にまとめて** 外部法務レビュー（データ受託化）（B6）

## Blockers

- ~~External installer MVP blocked until professional sign-off records merged.~~ **🟢 Resolved 2026-05-31 — both sign-offs on record.**
- Sibling apps need `NENE_SUITE_MODE` env readers (cross-repo Issues).
- Tier A suite wizard depends on sibling release ZIP installers (Invoice Phase 3).

## Handoff

Public meta repo. Compliance model mirrors nene-invoice `accounting-compliance.md`.
Binding trio: scope-contract + orchestration-compliance + disclaimer.

### VPS staging status — 2026-06-20

- The staging VPS is active for the staging host.
- A shared reverse proxy (Caddy) is attached to the external Docker network
  `edge`; the proxy and each environment root live in out-of-repo paths on the host.
- Suite staging runs from an out-of-repo environment root on the host.
- `.env.suite` is host-local and must not be committed.
- The staging host routes through the reverse proxy to the app container.
- Suite database is internal only; it is not attached to `edge` and has no
  published host ports.
- The staging `/health` endpoint returns HTTP 200.
- GitHub Actions staging deploy is active: `main` CI success triggers an SSH deploy
  to the host and reaches `health OK`. The deploy logic is repository-managed
  (`ops/staging/deploy-staging.sh`); the workflow pulls, then runs that script.
- Detailed daily report: `docs/daily/2026-06-20.md`.

### 2026-06-21

- Frontend now built into the image; staging serves the SPA at `/` (PR #120).
- Schema migrations apply automatically on container start (ADR 0014, PR #122);
  the staging `nene_suite` control DB has its tables.
- `main` is protected by ruleset `protect-main` (PR #118).
- Product direction set: self-hosted OSS + hosted **NeNe Cloud Free**
  (ADR 0015, draft). Anti-lock-in / data portability is the headline.
- phpMyAdmin runs as a VPS-local, out-of-repo compose project (SSH-tunnel only).
- Detailed daily report: `docs/daily/2026-06-21.md`.

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
- **B1（#329）install review の情報欠落を修正**: install-session entity/mapper に `databaseTargets`
  を通し、ReviewStep を friendly name＋per-app DBターゲット（provision / adopt server·name）＋org 名＋
  Clear→Invoice 連携の表示に刷新（未使用だった `review.*` キーを配線）。残り B（#330–#334）は順次。
- **監査ログ 一覧強化＋詳細ドロワー（before/after 差分ビューア）を ClaudeDesign 返却デザインで実装
  （#337・epic #327 backlog）**: `NeNe Suite のGUIデザイン (4).zip` を React + CSS Modules・oklch トークンへ
  移植。entity を `before`/`after`/`metadata`/`suiteId`/`orgExternalId`/`actorUserId`/`requestId` 込みに拡張、
  再帰 diff エンジン（pure・単体テスト）、行クリック→詳細ドロワー（メタ2列＋metadata＋unified/split 差分・
  色非依存の符号＋ラベル＋カラーバー・REDACTED チップ・変更のみ/全件トグル）、一覧は種別アイコン＋和文
  グロス＋作成/変更/削除バッジ＋人/マシン actor＋検索/ソース/種類フィルタ。read-only・light/dark・en+ja。
- **監査 CSV に before/after 同梱（#339）**: 税理士ペルソナ指摘（CSV が証跡にならない）に対応。
  `buildAuditCsv` を time/change/action/entity/actor/source/**before/after/metadata**/request_id/
  org_external_id/suite_id の証跡 CSV へ拡張（JSON は escapeCell で安全格納）。export が「ロード済み/
  フィルタ後」のみな点は別件（全件 export は後続）。
- **MFA/step-up 方針確定（ADR 0025・cross-repo #341）**: NeNe Clear から「MFA をどこに置くか」の相談
  （#341, Clear#195）。Suite=IdP・standalone 可（ADR 0014）・NENE2 共有層を踏まえ、**両モード対応**で確定:
  generic TOTP は **NENE2**（新リポ作らず・NENE2#1427 起票 → **v1.5.333 で出荷済み**・2026-07-02 実測）、
  **federated は Suite IdP が enforce＋assertion に
  step-up claim**、**standalone は sibling のローカル login が同じ TOTP を使う**。enroll=本人/enforce=管理者
  ポリシー（アプリ層）、break-glass は MFA 免除＋補償統制（recovery codes 必須・CLI）。**federation 待ちと
  decouple**（Clear は #195 を今進められる）。ADR 0012 を extend。#341 に Suite 回答を投稿済み。
- **トップメニュー→左サイドバー型シェルへ刷新（#343・B3 #331 解消）**: レスポンシブ対応で水平ナビ＋
  ボトムシートを撤去し、ClaudeDesign 返却（`nene-suite_sidemenu.zip`）の `[sidebar][main]` シェルを実装。
  サイドバー = ダークティール（`--side-*`）グループ化 IA（運用 Home/Catalog/Install・ガバナンス
  Organizations[superadmin]/Audit/Settings・サポート Help）。**≥1001px フルサイドバー / ≤1000px 74px
  アイコンレール / ≤680px オフキャンバスドロワー＋top-bar ハンバーガー＋オーバーレイ**。top-bar は検索⌘K＋
  ActiveOrg/通知/locale/theme/account。superadmin 専用 nav を非 superadmin に出さない（無言リダイレクト解消）。
  新規 AppSidebar、use-app-nav は grouped 化。SuiteMark/`--side-*` は既存流用。
- Detailed daily report: [`docs/daily/2026-06-27.md`](../daily/2026-06-27.md).
- ホーム挨拶の名前カラーピッカー（ClaudeDesign プレビュー用 UI）を撤去（#345）。名前は既定色 `--accent` 維持。
- Gate state: PHPUnit **468** / vitest **98**, all green（greet-color テスト2件撤去）.
- **a11y: command palette / Modal の focus trap・SR アナウンス（#332・B4 解消）**: 共有 `useFocusTrap`
  （mount で panel 内へフォーカス移動・Tab/Shift+Tab を trap・unmount で trigger へ復帰）を Modal/Drawer/
  CommandPalette に適用。palette は WAI-ARIA APG combobox 化（`role="combobox"`＋`aria-activedescendant`＋
  `aria-expanded`/`aria-controls`、option は managed-focus 化、empty は `role="status"`）。Account menu は
  menu-button パターン（open で先頭 menuitem へ・矢印巡回・Escape/Tab で trigger 復帰）、Notifications
  popover も trap＋復帰。install wizard stepper は `aria-current="step"`＋visually-hidden live region
  （`suite.install.wizard.step.announce`、en/ja）で SR に前進を通知し、`<ol>` 直下の装飾 span を擬似要素化。
- Gate state: PHPUnit **468** / vitest **110**, all green（focus/a11y テスト12件追加）.
- **help i18n: 英語ロケールでの日本語本文の不在シグナル＋シェル文言 i18n 化（#330・B2 解消）**:
  非 ja ロケールの全 /help ルートに「本文は日本語のみ・英語版準備中」の Callout 通知（ADR 0024 の
  日本語先行方針は維持・chrome は ADR 0009 どおり en+ja）。HelpLayout sidebar・HelpHome タスク配列・
  HelpGlossary chrome・HelpGuide not-found・guide group / glossary category 見出し・Callout SR プレフィックス
  （重要:/注意:）をメッセージカタログへ移行（`suite.help.*` 32 キー追加、en/ja parity）。en/ja 両ロケールの
  chrome テスト 6 件追加。
- Gate state: PHPUnit **468** / vitest **116**, all green.
- **org 無効化の可逆性明示・再有効化導線・影響範囲表示（#333・B5 解消）**: 新 API
  `POST /api/v1/organizations/{id}/enable`（superadmin 限定・idempotent・`organization.enabled` を
  before/after 付きで監査記録 — Disable の完全ミラー）。console の disable 確認に「可逆な凍結であり
  削除ではない／メンバーはサインイン不可／データは保持／いつでも再有効化可／監査記録」の影響範囲
  コピーを追加し、disabled 行の死んでいた Disable 項目を **Re-enable 導線（確認ステップ付き）** に置換。
  OpenAPI 31 → 32 operations・codegen 済・en/ja 6 キー追加。
- Gate state: PHPUnit **476** / vitest **118**, all green（enable use case/handler 8 件・console/entity 2 件追加）.
- **header LocaleToggle の非 en/ja 閉じ込め解消＋LocaleSwitcher マウント（#334・B6 解消 — persona-eval B
  group 完了）**: toggle を維持ペア（en+ja）へのクランプに変更 — ja→en / en→ja / **stub locale→en**
  （読んでいる fallback 言語へ・暗黙に ja へ落とさない）。デッドコードだった 6 言語 `LocaleSwitcher` を
  Settings → General → Language にマウント（全 locale が到達・脱出可能な唯一の picker）。クランプ仕様を
  `docs/development/i18n.md` に明文化。`resolveLocale` に zh-CN/zh-SG/zh → zh-Hans alias を追加
  （zh-TW/zh-Hant は en fallback のまま）。
- Gate state: PHPUnit **476** / vitest **124**, all green.
- **memberships 画面に編集対象 org のコンテキストバナーを追加（#327 medium sweep）**: URL の
  ULID だけでは編集中テナントが判らない問題に対し、console 上部に org 名＋slug＋（無効時）
  Disabled バッジを表示（superadmin org 一覧のキャッシュを再利用・`suite.member.orgContext`
  en/ja 追加）。
- Gate state: PHPUnit **476** / vitest **126**, all green.
- **zh-Hans フォントスタック修正（#327 medium sweep）**: 全 locale で Noto Sans JP 先行だったため
  簡体字ユーザに漢字が日本語字形で描画されていた問題を修正 — zh-Hans のみ SC フォント
  （Noto Sans SC / PingFang SC / Microsoft YaHei）先行に変更（JP フォントはスタック後方に残置）。
  Google Fonts link に Noto Sans SC を追加（css2 は使用時のみ遅延 DL）。
- Gate state: PHPUnit **476** / vitest **130**, all green.
- **org 一覧の絞り込み＋slug 規則の具体エラー（#327 medium sweep）**: 一覧に name/slug の
  クライアント側フィルタ（0 件時はプレースホルダ表示）を追加。作成フォームの slug に backend
  規則（`^[a-z0-9]+(?:-[a-z0-9]+)*$`・最大160字）をミラーしたクライアント検証＋具体的な
  ルール文言（en/ja）を表示。サーバ側ページングは list API 拡張が要るため defer（epic に記載）。
- Gate state: PHPUnit **476** / vitest **132**, all green.
- **install wizard の「戻る」導線＋Enter 送信（#327 medium sweep — sweep 完了）**: database /
  disclaimer / review 各 step に Back ボタン（既存 `goToStep` を配線・URL step param のみ変更で
  server session とは常に整合。backend は step 単位ゲート無しの `InProgress` 検証なので再送信で
  自然に前進できる）。apps step へ戻った際は session の selectedApps をプリフィル。AppSelection /
  Database step を `<form>` 化し text field からの Enter 送信に対応。
- Gate state: PHPUnit **476** / vitest **134**, all green.
- **cross-repo: nene-clear が installed-version probe と candidate preflight を採用（clear#182/#183 →
  clear PR#240/#241・2026-07-03）**: Suite の update diff は key ペアリング（`NENE2_MACHINE_API_KEY` ↔
  `NENE_SUITE_APP_NENE_CLEAR_MACHINE_KEY`）設定後に clear で実データ化。ADR 0023 slice①の read 消費も
  clear 相手に結線可能に。invoice（#496/#497）・records（#586/#648）は未採用。
- Detailed daily report: [`docs/daily/2026-07-02.md`](../daily/2026-07-02.md)
  （persona-eval B group 完了＋#327 medium sweep 完了＋鮮度更新の一日）.
- **#341 close（回答済み・ADR 0025 で確定・NENE2#1427 は v1.5.333 出荷済み）**。
- **O6 実装スライス起票（epic #251・ADR 0019 accepted 後）**: **#361**（S2-1a deploy-control seam＋
  監査・opt-in capability flag 既定 off）→ **#362**（S2-1b 依存順 plan＋min-version gating）→
  **#363**（S2-1c halt-don't-unwind 実行）→ **#364**（S2-1d apex「update all」UI）。Origin live 不要。
- Detailed daily report: [`docs/daily/2026-07-03.md`](../daily/2026-07-03.md)
  （ADR 0019 受理＋#341 close＋clear 採用＋O6 スライス起票）。Session handover:
  [`docs/handover/2026-07-03-ux-remediation-adr0019-o6-ready.md`](../handover/2026-07-03-ux-remediation-adr0019-o6-ready.md)（#365）.
- **ADR 0019 accepted（2026-07-02 amendment・O6 前提の最終ピース）**: OQ1 = host-side deploy agent
  （Suite に Docker socket を渡さない・allow-list 限定・監査必須・**opt-in capability flag（既定 off・
  全 edition で利用可）**）、OQ2 = 段階 provenance（catalog digest pin ＋ `/machine/health` 事後検証 →
  Origin 署名へ昇格）、OQ3（Tier A 共存）= toolkit 着地まで defer。O6（#251）実装スライスに着手可能。

Last updated: 2026-07-03

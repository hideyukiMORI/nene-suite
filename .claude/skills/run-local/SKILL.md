---
description: Start NeNe Suite backend (PHP 8800) and frontend (Vite 5188) for local development
---

# Run NeNe Suite locally

ローカル起動は **Docker ランタイム**（CLAUDE.md クイックスタート準拠）に統一。
起動手順は `run-local.sh` が「単一の真実」として所有する — このスキルはそれを実行するだけ。
（グローバル個人スキル `/dev-up` もこの repo では同じ `run-local.sh` を検出して委譲する。）

## 起動

```bash
bash .claude/skills/run-local/run-local.sh
```

`run-local.sh` がやること（すべて冪等）:

1. `docker compose --env-file .env.suite up -d` で backend を起動
   （apex `8800` / MySQL `3389`）。`--env-file .env.suite` は必須 —
   compose 冒頭の `${VAR:?...}` 補間は `.env` ではなく `.env.suite` から解決する
   設計のため（`docs/ops/staging-deploy.md` 参照）。
2. apex(`8800`) の HTTP 応答を待機（コンテナ entrypoint が `phinx migrate` を
   流してからサーブ開始する）。
3. frontend が未起動なら `cd frontend && npm run dev` を切り離して起動（Vite `5188`）。
   既に `5188` が応答していれば触らない。
4. backend / frontend の状態サマリを出す。いずれか失敗で非ゼロ終了。

## First-time setup

```bash
# 1. secret ファイルを用意（VPS-local・未コミット）
cp .env.suite.example .env.suite
# .env.suite を編集: DB パスワード等の必須シークレットを埋める

# 2. Docker エンジン（Docker Desktop）を起動しておく

# 3. 初回のみ: 組織ブートストラップ（最初の operator・disclaimer・app DB・manifest）。
#    install.php はマイグレーションも適用する。
docker compose --env-file .env.suite run --rm suite php installer/install.php
```

> スキーマのマイグレーションは冪等で、サーバ起動ごとにコンテナ entrypoint が
> 自動適用する（[ADR 0014](../../../docs/adr/0014-schema-migration-lifecycle.md)）。
> 通常の起動は `run-local.sh`（= `docker compose up -d`）だけでスキーマが最新になる。

## Verify

```bash
curl -o /dev/null -w "apex:  %{http_code}\n" http://localhost:8800/health
curl -o /dev/null -w "vite:  %{http_code}\n" http://localhost:5188/
```

## Stop

```bash
docker compose --env-file .env.suite down   # backend
pkill -f vite                               # frontend
```

## Key URLs

| URL | Description |
|---|---|
| http://localhost:5188/ | Frontend (login → launcher) |
| http://localhost:8800/health | Backend health check |
| http://localhost:8800/api/v1/catalog/apps | App catalog (unauthenticated) |
| http://localhost:8800/api/v1/auth/session | Login (POST) |

## ポート（CLAUDE.md ポート表に固定）

| Service | Port |
|---|---|
| Apex HTTP | **8800** |
| MySQL (control DB) | **3389** |
| Vite dev server | **5188** |
</content>
</invoke>

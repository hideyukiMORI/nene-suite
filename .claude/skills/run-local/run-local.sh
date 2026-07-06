#!/usr/bin/env bash
# run-local.sh — NeNe Suite のローカル開発スタックを冪等に起動する「単一の真実」。
#
# Docker ランタイム（CLAUDE.md クイックスタート準拠）:
#   backend  = docker compose --env-file .env.suite up -d   (apex 8800 / MySQL 3390)
#   frontend = npm run dev                                   (Vite 5188)
#
# このスクリプトを唯一の起動経路にすることで二重管理を避ける:
#   - グローバル個人スキル /dev-up は、この repo を対象にすると本スクリプトを
#     検出して丸ごと委譲する（~/.claude/skills/dev-up）。
#   - プロジェクトスキル run-local（SKILL.md）も本スクリプトを実行するだけ。
#
# ポートは CLAUDE.md のポート表に固定（8800 / 3390 / 5188）。ハードコード禁止のデフォルトは使わない。
#
# 重要: compose 冒頭の ${VAR:?...} 補間は .env ではなく .env.suite から解決する設計のため
#       --env-file .env.suite が必須（docs/ops/staging-deploy.md 参照）。
set -uo pipefail

# --- repo ルート（このスクリプトは .claude/skills/run-local/ に置かれている） ---
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "$HERE" && git rev-parse --show-toplevel 2>/dev/null || true)"
[ -n "$ROOT" ] || ROOT="$(cd "$HERE/../../.." && pwd)"
cd "$ROOT" || { printf '❌ repo ルートに移動できません: %s\n' "$ROOT" >&2; exit 1; }

say() { printf '%s\n' "$*"; }
ENVFILE=".env.suite"
APEX_URL="http://localhost:8800/"
VITE_URL="http://localhost:5188/"
FE_LOG="/tmp/nene-suite-frontend.log"

say "▶ NeNe Suite ローカル起動 (Docker)  repo: ${ROOT}"
say ""

backend="failed"
frontend="skip"

# --- 前提: .env.suite（VPS-local の secret 群。未コミット） ---
if [ ! -f "$ROOT/$ENVFILE" ]; then
  say "❌ ${ENVFILE} がありません。"
  say "   cp .env.suite.example ${ENVFILE} して、必須シークレット（DB パスワード等）を埋めてください。"
  exit 1
fi

# ============================================================================
# backend: docker compose
# ============================================================================
if ! docker info >/dev/null 2>&1; then
  say "❌ Docker エンジンが動いていません。Docker Desktop を起動してから再実行してください。"
  exit 1
fi

say "🚀 docker compose --env-file ${ENVFILE} up -d"
docker compose --env-file "$ENVFILE" up -d 2>&1 | tail -12
rc=${PIPESTATUS[0]}
say ""
say "📦 コンテナ状態:"
docker compose --env-file "$ENVFILE" ps 2>&1

if [ "$rc" -ne 0 ]; then
  say "❌ docker compose の起動に失敗しました（exit ${rc}）。上のログを確認してください。"
else
  # apex の HTTP 応答待ち（entrypoint が phinx migrate を流してからサーブ開始する）
  say ""
  say "⏳ apex(8800) の応答を待機中…"
  for _ in $(seq 1 20); do
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 4 "$APEX_URL" 2>/dev/null || true)"
    case "$code" in 200|301|302|401|404) backend="ok"; break ;; esac
    sleep 2
  done
  if [ "$backend" = "ok" ]; then
    say "✅ backend: apex 応答 OK (${APEX_URL})"
  else
    say "⚠️ apex(8800) が応答しません。docker compose --env-file ${ENVFILE} logs suite を確認してください。"
  fi
fi

# ============================================================================
# frontend: vite (npm run dev) — 冪等。既に 5188 が応答していれば触らない。
# ============================================================================
if curl -fsS -o /dev/null --max-time 3 "$VITE_URL" 2>/dev/null; then
  say "✅ frontend: 既に起動済み (${VITE_URL})"
  frontend="already"
elif [ -f "$ROOT/frontend/package.json" ]; then
  say "🚀 フロント dev 起動: (cd frontend && npm run dev)  log: ${FE_LOG}"
  : > "$FE_LOG"
  # setsid でセッションから切り離し、スクリプト終了後も生存させる
  setsid bash -c "cd '$ROOT/frontend' && exec npm run dev" >"$FE_LOG" 2>&1 < /dev/null &
  for _ in $(seq 1 20); do
    curl -fsS -o /dev/null --max-time 2 "$VITE_URL" 2>/dev/null && { frontend="started"; break; }
    sleep 1
  done
  if [ "$frontend" = "started" ]; then
    say "✅ frontend: 起動完了 (${VITE_URL})"
  else
    frontend="failed"
    say "❌ frontend: 起動失敗。log 末尾:"
    tail -20 "$FE_LOG" 2>/dev/null
  fi
else
  say "ℹ️ frontend/package.json なし → フロントはスキップ"
fi

# ============================================================================
# サマリ
# ============================================================================
say ""
say "──────────── run-local サマリ ────────────"
say "  backend  : [${backend}]  ${APEX_URL}  (MySQL :3390)"
say "  frontend : [${frontend}]  ${VITE_URL}"
say "──────────────────────────────────────────"
say "停止: docker compose --env-file ${ENVFILE} down  /  フロントは pkill -f vite"

[ "$backend" = "failed" ] && exit 1
[ "$frontend" = "failed" ] && exit 1
exit 0

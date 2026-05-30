#!/usr/bin/env bash
# Scan repository docs/catalog for forbidden terminology variants.
# Canonical list: docs/explanation/terminology.md
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

FAIL=0

scan() {
  local label="$1"
  local pattern="$2"
  local paths=("${@:3}")
  if rg -n --pcre2 "$pattern" "${paths[@]}" 2>/dev/null; then
    echo "FAIL: forbidden pattern ($label)" >&2
    FAIL=1
  fi
}

PATHS=(README.md AGENTS.md DISCLAIMER.md docs catalog .env.suite.example .cursor)

# Env prefix typos (allow NENE_SUITE_MODE)
scan "SUITE_MODE without NENE_" '(?<![A-Z_])SUITE_MODE' "${PATHS[@]}"
scan "NENE2_SUITE_" 'NENE2_SUITE_' "${PATHS[@]}"

# Catalog / marketing forbidden (terminology §11)
scan "compliant out of the box" 'compliant out of the box' "${PATHS[@]}"
scan "audit-ready" 'audit-ready' "${PATHS[@]}"
scan "税理士不要" '税理士不要' "${PATHS[@]}"
scan "unified ledger" 'unified ledger' "${PATHS[@]}"

# Broken sibling relative paths in docs
scan "../nene- sibling path" '\.\./nene-[a-z]+/' docs

# accounting-compliance.md must not be linked as in-repo path
scan "local accounting-compliance link" '(\./|\.\./)[^)]*accounting-compliance\.md' docs

if [[ "$FAIL" -ne 0 ]]; then
  echo "Terminology check failed. See docs/explanation/terminology.md" >&2
  exit 1
fi

echo "Terminology check passed."

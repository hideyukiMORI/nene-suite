#!/usr/bin/env node
/*
 * W1 suite stage1 — deterministic CSS custom-property rename (規約 05 §9.2 W1 行).
 *
 * WHY A BUNDLED SCRIPT INSTEAD OF THE CODEMOD (M-1 honesty):
 * `@hideyukimori/nene2-tokens` codemod (`mapTokenName`) cannot rename suite's
 * vocabulary. Suite tokens are prefix-less (`--bg`, `--fg`, `--brand`, `--r`) —
 * they carry no `--color-`/`--shadow-` prefix, so the codemod's color/shadow
 * mapping branches never see them. Empirically (mapTokenName(name,'common')):
 *   - 14 single-segment names return NULL (rejected): --bg --surface --border
 *     --fg --brand --accent --ink --ok --warn --danger --origin --dot --shadow --r
 *   - every hyphenated color name is x-sent under its WRONG existing prefix
 *     (--fg-2 -> --fg-x-2, --surface-2 -> --surface-x-2, --brand-strong ->
 *     --brand-x-strong, --side-bg -> --side-x-bg, --hero-1 -> --hero-x-1, ...),
 *     never reaching the contract vocabulary (--color-text-muted, ...).
 *   - only 6/44 come out usable (--font-x-*, --r-x-sm, --r-x-pill, --shadow-lg).
 * There is also NO `suite` mapping table (only common/origin/vault). Tracked as a
 * fleet-tooling issue proposing a SUITE_TABLE + prefix-less handling.
 *
 * This script encodes the equivalent suite mapping (contract v1 vocabulary where a
 * semantic slot exists — vault precedent bg->surface / navy(brand)->accent /
 * line->border etc.; x- extension elsewhere; typography/radius are v1 scope-out ->
 * x- per AM-3). VALUES ARE NEVER TOUCHED — names only, `:root` stays (stage2/W6
 * does `:root`->`@theme`). Re-runnable & idempotent (targets are not sources).
 *
 * Usage:  node scripts/w1-stage1-token-rename.mjs [--check]
 *   --check : exit 1 if any legacy name remains (no writes) — CI/verification mode.
 */
import { readFileSync, writeFileSync } from 'node:fs'
import { execSync } from 'node:child_process'
import { fileURLToPath } from 'node:url'
import { dirname, join } from 'node:path'

const FRONTEND = join(dirname(fileURLToPath(import.meta.url)), '..')

/** old bare name (without leading `--`) -> new contract/extension name (with `--`). */
const SUITE_MAP = {
  // ── surfaces (contract) ──────────────────────────────────────────────
  bg: '--color-surface',
  surface: '--color-surface-raised',
  'surface-2': '--color-surface-overlay',
  'surface-3': '--color-surface-sunken',
  // ── borders (contract) ───────────────────────────────────────────────
  border: '--color-border',
  'border-2': '--color-border-strong',
  // ── text (contract) ──────────────────────────────────────────────────
  fg: '--color-text-primary',
  'fg-2': '--color-text-muted',
  'fg-3': '--color-text-faint',
  // ── accent (contract — suite's semantic aliases become the contract seat) ─
  accent: '--color-accent',
  'accent-soft': '--color-accent-soft',
  'on-brand': '--color-on-accent',
  // ── brand primitives (x- extension; accent/*-soft derive from these) ─────
  brand: '--color-x-brand',
  'brand-strong': '--color-x-brand-strong',
  'brand-deep': '--color-x-brand-deep',
  'brand-soft': '--color-x-brand-soft',
  'brand-softer': '--color-x-brand-softer',
  'side-brand': '--color-x-side-brand',
  ink: '--color-x-ink',
  // ── status (contract) ────────────────────────────────────────────────
  ok: '--color-success',
  warn: '--color-warn',
  danger: '--color-danger',
  // ── product/domain color (x-) ────────────────────────────────────────
  origin: '--color-x-origin',
  // ── dark sidebar chrome (x-) ─────────────────────────────────────────
  'side-bg': '--color-x-side-bg',
  'side-bg-2': '--color-x-side-bg-2',
  'side-fg': '--color-x-side-fg',
  'side-fg-mut': '--color-x-side-fg-mut',
  'side-active': '--color-x-side-active',
  'side-line': '--color-x-side-line',
  // ── marketing hero gradient (x-) ─────────────────────────────────────
  'hero-1': '--color-x-hero-1',
  'hero-2': '--color-x-hero-2',
  'hero-3': '--color-x-hero-3',
  'hero-accent': '--color-x-hero-accent',
  'hero-bd': '--color-x-hero-bd',
  // ── misc color (x-) ──────────────────────────────────────────────────
  'logo-ring': '--color-x-logo-ring',
  dot: '--color-x-dot',
  // ── shadow (contract) ────────────────────────────────────────────────
  // NOTE: `--shadow-lg` is already the contract name (SHADOW_KEYS has `lg`) — it is
  // intentionally NOT in this map and stays untouched (the bare `--shadow` rule's
  // `(?![a-z0-9-])` lookahead leaves `--shadow-lg` alone).
  shadow: '--shadow-md',
  // ── typography (v1 scope-out -> x-) ──────────────────────────────────
  'font-sans': '--font-x-sans',
  'font-num': '--font-x-num',
  'font-serif': '--font-x-serif',
  // ── radius (v1 scope-out -> x-) ──────────────────────────────────────
  r: '--r-x-base',
  'r-sm': '--r-x-sm',
  'r-pill': '--r-x-pill',
}

// Longest-first alternation so `--brand-strong` wins over `--brand`; the negative
// lookahead `(?![a-z0-9-])` guarantees whole-token matches (never a prefix of a
// longer custom property). Single pass — produced targets are never re-matched.
const keys = Object.keys(SUITE_MAP).sort((a, b) => b.length - a.length)
const RE = new RegExp(
  `--(?:${keys.map((k) => k.replace(/[-]/g, '\\-')).join('|')})(?![a-z0-9-])`,
  'g',
)

const files = execSync(`git -C "${FRONTEND}" ls-files 'src/*.css' 'src/*.ts' 'src/*.tsx'`, {
  encoding: 'utf8',
})
  .split('\n')
  .filter(Boolean)
  .map((f) => join(FRONTEND, f))

const check = process.argv.includes('--check')
let totalHits = 0
const perFile = []
for (const file of files) {
  const src = readFileSync(file, 'utf8')
  let hits = 0
  const out = src.replace(RE, (m) => {
    hits += 1
    return SUITE_MAP[m.slice(2)]
  })
  if (hits > 0) {
    totalHits += hits
    perFile.push([file.replace(`${FRONTEND}/`, ''), hits])
    if (!check) writeFileSync(file, out)
  }
}

for (const [f, n] of perFile) console.log(`${String(n).padStart(4)}  ${f}`)
console.log(
  `\n${check ? 'REMAINING legacy refs' : 'renamed'}: ${totalHits} across ${perFile.length} files`,
)
if (check && totalHits > 0) process.exit(1)

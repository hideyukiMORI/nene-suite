# Dependency vulnerability gate (frontend)

Every PR runs a dependency audit as a **merge gate**. This document says what the gate is,
how an exception is granted, and what is currently excepted.

- Config: [`frontend/audit-ci.jsonc`](../../frontend/audit-ci.jsonc) (the file itself carries
  the reasoning for each entry — keep the two in sync)
- Command: `npm run audit --prefix frontend`
- CI: the `Audit (fail on high/critical)` step of `Frontend (type-check + tests)`

## The gate

`audit-ci` fails the build on any **high** or **critical** advisory that is not explicitly
allowlisted. Moderate and below do not fail (they are still reported).

We use `audit-ci` rather than bare `npm audit --audit-level=high` for one reason: **`npm audit`
has no way to record a reasoned exception.** Without one, the only ways past a not-yet-fixable
advisory are to lower the severity threshold or drop the step — both of which blind the gate to
*everything*, not just the advisory in question.

## Rules for an exception

1. **Per advisory id, never per severity.** Allowlist `GHSA-…`; do not raise `--audit-level`
   and do not set `high: false`. A new advisory must still fail the build the day it lands.
2. **The reason must be measured, not assumed.** State why the vulnerable code path does not
   exist *in this codebase*, and how that was checked (a grep, a build artifact, a config).
   "We probably don't use that" is not a reason.
3. **Every entry has an expiry** and a named condition that removes it (an upgrade wave, an
   upstream fix). An expired entry is a task — re-argue it in a PR; do not extend it by reflex.
4. **Prefer the fix.** If a patched version exists in a range we can take, take it. An
   exception is only for "no fix exists that we can adopt".

## What was fixed rather than excepted (2026-07-29)

`npm audit fix` plus two `overrides` closed five of the seven advisories that were open before
this gate landed:

| Package | Before | After | Advisory |
| --- | --- | --- | --- |
| `vite` | 8.0.14 | 8.1.5 | GHSA-v6wh-96g9-6wx3, GHSA-fx2h-pf6j-xcff |
| `postcss` | 8.5.17 | 8.5.24 | GHSA-r28c-9q8g-f849 |
| `react-router` / `-dom` | 7.16.0 | 7.18.1 | GHSA-wrjc-x8rr-h8h6, GHSA-h8fp-f39c-q6mh, GHSA-337j-9hxr-rhxg, GHSA-chx6-hx7r-mcp5 |
| `js-yaml` | 4.1.1 | 4.3.0 (override) | GHSA-h67p-54hq-rp68, GHSA-52cp-r559-cp3m |
| `brace-expansion` (top level) | 1.1.16 | 5.0.8 | GHSA-3jxr-9vmj-r5cp |

`js-yaml` needs an override because `@redocly/openapi-core` pins it **exactly** (`"js-yaml":
"4.2.0"`), so no amount of `npm update` reaches 4.3.0. Verified compatible by running
`npm run codegen`, which is the only consumer of that dependency.

### Why the `brace-expansion` override is scoped

The obvious fix — a blanket `"brace-expansion": "^5.0.8"` — is **wrong here**, and quietly so.
It hoists v5 into `minimatch@3`, whose CommonJS `require('brace-expansion')` expects the module
itself to be callable; v5 exports `{ expand }`, so the call throws `expand is not a function`.

Measured in this tree on 2026-07-29:

```
# under a blanket override
node -e "require('./node_modules/eslint-plugin-import/node_modules/minimatch')('src/ab.ts','src/a{b,c}.ts')"
#   → TypeError: expand is not a function

# under the scoped override that shipped
#   → true
```

Probing **one** minimatch is not enough. Enumerated across this tree on 2026-07-29, a flat
override breaks **three of the four** installed copies:

| minimatch copy | version | flat override | scoped override |
| --- | --- | --- | --- |
| `eslint-plugin-import/node_modules/minimatch` | 3.1.5 | ❌ throws | ✅ |
| `eslint-plugin-jsx-a11y/node_modules/minimatch` | 3.1.5 | ❌ throws | ✅ |
| `@redocly/openapi-core/node_modules/minimatch` | 5.1.9 | ❌ throws | ✅ |
| `node_modules/minimatch` (top level) | 10.2.5 | ✅ | ✅ |

…and yet `npm run check` **and** `npm run codegen` both stay green under the flat override,
because nothing we run happens to pass a brace pattern. That is what makes the blanket version a
landmine rather than a fix: it breaks on the day someone adds a brace pattern to an ignore list
or a Redocly glob. The override is therefore scoped to `brace-expansion@5` (pin the v5 line
forward; leave the v1/v2 copies to their parents), and the residual advisory is allowlisted with
the dev-only evidence below.

### The permanent guard

`frontend/tests/toolchain/brace-expansion-override.test.ts` walks `node_modules`, resolves
**every** installed minimatch and runs a brace pattern through it, so a future override that
reintroduces the incompatibility fails a test instead of hiding behind a green build. Verified
sharp in this tree: under the flat override it fails 3 of its 5 cases; under the shipped scoped
override all 5 pass.

> Fleet note: `nene-invoice` #732 hit this as a *broken lint*; `nene-clear` hit it on the
> *codegen* path. Suite hid it on both. Same cause, three different symptoms — which is why the
> guard checks the mechanism rather than any one command. The guard itself comes from
> `nene-deal` #175.

## Current exceptions

| Advisory | Package | Why it does not apply here | Expires |
| --- | --- | --- | --- |
| [GHSA-qwww-vcr4-c8h2](https://github.com/advisories/GHSA-qwww-vcr4-c8h2) | `react-router` (7.12.0–8.2.0) | The apex shell is a **static SPA built by Vite** and copied into the Apache document root by the Dockerfile's frontend stage — **there is no Node server in the image**. `src/app/router.tsx` uses `createBrowserRouter` with **element-only** routes. Measured 2026-07-29: no route `action:` / `loader:` key anywhere in `src/`, no `@react-router/dev`, no `react-router/server`, no `createStaticHandler`, no RSC entry, no SSR render/hydrate call. The advisory's attack path (a server executing a route action before returning 400) has no counterpart in a client-only bundle. | **2026-08-31** |
| [GHSA-mh99-v99m-4gvg](https://github.com/advisories/GHSA-mh99-v99m-4gvg) | `brace-expansion` (≤5.0.7), nested copies only | The top-level copy **is** fixed (5.0.8). What remains are `minimatch@3`'s copy (eslint-plugin-import, eslint-plugin-jsx-a11y → 1.1.16) and `minimatch@5`'s (@redocly/openapi-core → 2.1.3), which cannot be forced without the breakage documented above. Measured 2026-07-29: `npm ls --omit=dev --all brace-expansion` is **empty** (as are the same queries for `minimatch` and `js-yaml`) — none of these reach the shipped bundle. Their inputs are repo-authored lint globs and our own OpenAPI file, not attacker-controlled data. | **2026-08-31** |

`react-router` has **no fix in the 7.x line**: `react-router-dom` ends at 7.18.1 and the fix
lands in `react-router` v8 (≥ 8.2.1) — a different package and a breaking upgrade. That
exception is removed by the **react-router v8 migration wave** (bundled with the NENE2 RR8
re-evaluation).

The `brace-expansion` exception is written against a **resolution fact rather than a release
name**: it goes away when `eslint-plugin-import` / `eslint-plugin-jsx-a11y` stop resolving
`minimatch@3` and `@redocly/openapi-core` stops resolving `minimatch@5` — i.e. when the guard
test finds only copies a flat `brace-expansion@^5` override would not break. Check it by
re-running the guard, not by reading a changelog.

## Fleet note

The shape of this setup is the fleet reference implementation (`nene-contact` #524, 施主 GO
2026-07-29); the scoped-override correction comes from `nene-invoice` #732. Sibling products may
copy it — but each must **re-measure the claims in its own tree before copying an allowlist
entry**. Copying an exception without re-measuring is exactly the failure mode the rules above
exist to prevent: suite's `npm run lint` would have "confirmed" a blanket override that is
provably broken.

## Related

- [`coding-standards.md`](./coding-standards.md) — the wider merge-gate set
- [`frontend-standards.md`](./frontend-standards.md) — `npm run check` (the other frontend gate)
- Pinning a version to dodge an advisory is a **time-limited** measure, not a fix: the pinned
  version can itself fall inside a later advisory. Prefer ranges, and revisit pins.

# ADR 0022: App Onboarding Modes — Suite-Driven Install and Standalone-First Join

## Status

proposed (2026-06-26)

Concretizes **ADR 0012 §7 (enrollment) + §8 (self-registration)** to the level that lets the
suite-driven adopt entry point ship now while staying **forward-compatible** with the standalone-first
inbound join. Builds on **ADR 0021** (the per-app database target / adopt mechanism) and **ADR 0010**
(install manifest). Does not supersede ADR 0012.

## Context

ADR 0021 made an app's database a per-app **target** (`provision` | `adopt`, configurable server) and
shipped the engine + manifest recording; adopt already works via env (`NENE_SUITE_APP_{SNAKE}_DB_*`).
What is missing is the **operator-facing entry** to choose adopt — and that entry has two genuinely
different shapes:

- **Suite-driven install (mode A).** The operator runs the suite installer and, per app, picks
  `provision` or `adopt` (supplying the existing `{server, database name}` for adopt). The suite drives
  the install and points the app at the chosen database. Implementable now, Suite-contained.
- **Standalone-first inbound join (mode B).** An app **already running standalone** — with its own
  database, organization, and users — joins the suite. The suite did *not* install it; the app
  registers itself **inbound** (ADR 0012 §7 enrollment + §8 self-registration). Its existing database
  is **adopted** (data plane) and its existing org/users are **linked** (identity plane, ADR 0012 §6).
  This is the real "bring an existing tool under the suite" case, and it depends on the federation
  login / org-resolution work (milestone **B2**) that is not yet built.

Both are needed eventually. The risk this ADR removes: if mode A is designed in isolation — e.g. the
database target carried **only** on the install-session — then mode B, which has **no install-session**
(the suite never installed the app), would need a parallel path or force mode A to be reworked. The
fix is to define **one onboarding model** that both modes feed, lock the shared shape now, and defer
the B2-dependent specifics. (Implementing both at once is not available: mode B is blocked on B2 and
the unbuilt §7/§8, partly cross-repo — so the safety of "do it all at once" is bought at the
**contract** level, not the implementation level.)

Constraints (inherited, unchanged):

- The app is SSOT for its domain; the suite holds identity / roster only; join and leave are
  non-destructive, reversible, and move no data (ADR 0012 §1/§11/§12).
- Adopt is **register-only** — no DDL/DML on an adopted database (ADR 0021 §3).
- One database per app, no shared schema, HTTP-only integration (orchestration-compliance §3; ADR 0002).
- The sibling side of an inbound join is a **generic** framework capability ("join an upstream hub"),
  never Suite-named (recorded principle — NENE2 stays suite-agnostic).

## Decision

### 1. One onboarding model, two entry modes

An app comes under the suite via an **onboarding** that produces a single descriptor, regardless of
entry mode:

- `catalog_id`, public URL
- **database target** (ADR 0021): `mode` (`provision` | `adopt`), `server`, database name
- **identity link** (when applicable): existing org by `org_external_id`, existing users by `email`
  (ADR 0012 §5/§6) — present for a standalone-first join, absent/empty for a fresh suite-driven install
- **provenance**: `suite_installed` (the suite created it, ADR 0010) vs `self_registered` (the app
  registered inbound, ADR 0012 §8)

Mode A and mode B are **two producers of this same descriptor**; everything downstream (provisioning,
manifest, launcher, federation) consumes the descriptor, **not** the entry mode.

### 2. The database target is not install-session-exclusive (the decoupling that protects mode A)

The per-app database target is a **first-class onboarding input**, not a field only the install-session
can carry. Mode A populates it through the install-session flow; mode B populates it through the inbound
registration. The provisioning + manifest path already consumes a `DatabaseTarget` (ADR 0021) — both
modes feed **that same path**. Mode A's data model and API surface MUST be shaped so the inbound flow
reuses them, not so they hard-bind to the install-session.

### 3. Mode A — suite-driven install adopt (implementable now)

- The install-session carries an **optional per-app database target override** (`provision` default;
  `adopt` with `{server, name}`), resolved as **session override → env (`NENE_SUITE_APP_{SNAKE}_DB_*`)
  → default**.
- Operator entry on all three surfaces: the install wizard (per-app DB config), an `app-selection` /
  dedicated configure-database HTTP operation, and the CLI (already via env).
- The identity plane is **out of scope for mode A**: a suite-driven install points an app at a
  database; it does not link a pre-existing org / users (a fresh install has none). Mode A is the
  **data-plane-only** entry.
- Reuses ADR 0021 validation (mode enum, external = adopt-only in the MVP, safe name) and records
  `mode` / `server` in the manifest (already shipped, ADR 0021 impl ②).

### 4. Mode B — standalone-first inbound join (after B2)

- **§7 enrollment.** The suite exposes a one-time enrollment-token → credential exchange; the app
  initiates join (hub URL + token) and receives a machine service credential + IdP config. The
  sibling side is a **generic** "join an upstream hub" capability.
- **§8 self-registration.** The installed-apps / manifest model is extended to accept an
  **externally-installed** app registering inbound with its own onboarding descriptor — its existing
  database (adopt) and its identity link (existing org `external_id`, users by `email`).
- Identity-plane linking and org resolution depend on the federation login flow (**B2**); mode B's
  full build waits for it.

### 5. Invariants across both modes

- Adopt is register-only (ADR 0021 §3); join is non-destructive and reversible (ADR 0012 §1/§11/§12);
  the app stays SSOT for its domain; the suite never writes domain rows.
- No secret in the manifest / audit — `server` is a non-secret label; runtime credentials stay the
  app's own `database.env_prefix` (ADR 0004).

## Open questions

- **OQ1 — mode A target threading (resolve at acceptance; needed for mode A).** The exact shape of the
  per-app target override on the install-session, and the API surface — extend `updateAppSelection`
  vs a dedicated configure-database-targets operation.
- **OQ2 — registration entity (defer — B2).** Does inbound self-registration extend the
  `install_manifest` / installed-apps entity (ADR 0010), or add a parallel `registered_app` record?
- **OQ3 — identity reconciliation (defer — B2).** How an inbound app's existing org (`external_id`)
  and users (by `email`) reconcile with the suite directory, and conflict handling — depends on the
  federation org resolution (B2).
- **OQ4 — provenance & trust (defer — B2).** A self-registering app asserts its own DB / org; how much
  the suite verifies vs trusts (the app is domain SSOT, ADR 0012 §11), plus the enrollment-token
  security model (§7) — an implementation-time security review, as ADR 0012 scheduled for its IdP path.

## Terminology registry impact (ADR 0006)

Mode A introduces identifiers (the install-session target-override fields + any new API operation);
they register with the **mode A implementation PRs** (ADR 0018 precedent), **reusing** the ADR 0021
§4.4 database-target vocabulary (`provision` / `adopt`, `NENE_SUITE_APP_{SNAKE}_DB_*`). Mode B /
enrollment / provenance identifiers register when mode B is built. **No `terminology.md` change at
acceptance.**

## Consequences

**Benefits.** Mode A ships now without painting mode B into a corner: both entries feed one descriptor,
so the inbound join reuses the data-plane path rather than duplicating it. The standalone-first
"bring your existing tool" contract is fixed at the level that matters for forward-compatibility, with
the B2-dependent specifics honestly deferred.

**Costs / follow-up.** Mode A — install-session target override + API + wizard + tests. Mode B — §7
enrollment + §8 inbound registration + identity linking (after B2), plus the sibling-side generic
"join a hub" feature (cross-repo, never Suite-named).

**Risks.** Designing mode A before mode B's full specifics risks a mismatch → mitigated by §2 (the
decoupling) plus keeping mode B's deep specifics as open questions resolved at B2. A self-registering
app asserting its own state (mode B) is a trust surface → OQ4 + ADR 0012 §11 (the app stays domain SSOT).

## Related

- **Concretizes**: ADR 0012 §7 (enrollment), §8 (self-registration) — extends, does not supersede.
- **Builds on**: ADR 0021 (per-app database target / adopt), ADR 0010 (install manifest), ADR 0002
  (orchestrator), orchestration-compliance §3, ADR 0004 (env contract), ADR 0015 §5 (portability /
  data custody).
- Milestone: B2 (federation org resolution) — mode B's blocker.
- Issue: `#287`. PR: `#000` (TBD).
- Superseded by: none.

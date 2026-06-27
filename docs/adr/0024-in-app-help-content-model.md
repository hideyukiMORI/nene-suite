# ADR 0024: In-App Help Content Model

## Status

accepted

## Context

NeNe Suite has grown enough features (multi-app install, per-app database targets,
organizations / memberships / roles, federation, Origin feeds) that the apex shell is
harder to operate — especially for the target audience: small-business operators who are
not used to computers or the internet.

A sibling product, **NeNe Origin**, shipped an in-app help system on 2026-06-26 (glossary
／各画面の使い方／チュートリアル; PR #143–#147). We mirror that proven pattern, adapted to
NeNe Suite's stack (Feature-Sliced Design, CSS Modules + oklch design tokens, en+ja i18n).

[ADR 0009](0009-ui-message-catalogs-i18n.md) requires UI strings to go through the i18n
message catalogs (English is the source of truth; Japanese must reach parity). Help **prose**
— a glossary of 20+ cross-referenced terms plus multi-section guides — is high-volume,
heavily cross-referential, and **Japanese-first by product decision** (the audience is
Japanese SMB operators; English help follows later). Forcing that body through flat i18n keys
would be unwieldy and would impose a 2× en/ja parity burden before any English help exists.

## Decision

1. **A `features/help/` slice owns in-app help**: structured TypeScript content
   (`content/glossary.ts`, `content/guides.ts`, `content/types.ts`, `content/search.ts`) plus
   presentational UI (`ui/HelpHome`, `HelpGlossary`, `HelpGuide`, `HelpLayout`, `HelpText`,
   `Callout`). Routes `/help`, `/help/glossary`, `/help/:slug`, plus an apex nav entry.

2. **Help prose is structured TypeScript, not i18n message keys** — a deliberate, **scoped
   exception to ADR 0009**. The help body is **Japanese-first**; an English body follows in a
   later slice. Rationale: volume, cross-references, and a Japanese-first audience.

3. **UI chrome stays i18n'd (en+ja)** per ADR 0009 — the nav label, the "How to use this page"
   entry, help-home labels, and the `InfoHint` aria-label all live in the message catalogs.
   Only the help **body** (glossary / guide text) is the exception.

4. **Discovery is explicit navigation**, mirroring NeNe Origin: a `HelpLink`
   ("このページの使い方") in screen headers, auto-linked glossary chips (`HelpText` turns bare
   identifiers such as `provision` into glossary links), and a searchable glossary. **No hover
   tooltips** — explicit links are simpler for mobile, accessibility, and maintenance.

5. For the hardest individual form fields, a small click-to-open **`InfoHint`** ("?") inline
   annotation (keyboard- and screen-reader-friendly) **supplements** — it does not replace —
   the help pages. This is the "tooltip-like" affordance, kept accessible.

6. **Delivered in phases.** PR1 (this ADR) ships the help infrastructure, a glossary seed,
   the getting-started / concepts / roles / safety / FAQ guides, the install-wizard guide, and
   wires `HelpLink` + `InfoHint` into the install wizard. Later PRs cover the remaining screens
   (organization / membership consoles, audit, dashboard, catalog) and the English body.

## Consequences

**Benefits.**

- Beginner-friendly coverage for non-technical operators, consistent with NeNe Origin.
- Help content is plain TypeScript — easy to extend and review, with its own unit tests.
- Accessibility-first: no hover-only affordances; severity is never color-only (`Callout`
  carries an icon + screen-reader prefix).
- UI chrome stays fully localized (en+ja).

**Costs.**

- The help body is Japanese-only until the English slice lands.
- The structured-content exception to ADR 0009 must be remembered — it is recorded here.
- Help body is **not** covered by the i18n parity test (it is plain TypeScript); guide/glossary
  integrity is covered by feature unit tests instead.

**Follow-up.**

- English help body; remaining per-screen guides; optional annotated figures and a first-run
  tour (deferred).

## Related

- Issue: `#323`
- Reference: NeNe Origin in-app help (PR #143–#147, separate repo)
- Relates to: [ADR 0009](0009-ui-message-catalogs-i18n.md) — scoped exception for the help body
- Supersedes: none
- Superseded by: none

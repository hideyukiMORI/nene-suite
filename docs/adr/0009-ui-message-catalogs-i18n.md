# ADR 0009: UI Strings via Typed Message Catalogs (i18n)

## Status

accepted

## Context

NeNe Suite targets operators who switch language at runtime (Japanese + English
minimum; portfolio aligns with NENE2 six-locale set). Hardcoded JSX strings block
smooth locale switching, break compliance copy consistency, and diverge from
nene-records patterns already proven in production admin UI.

Repository docs remain **English**; **operator-facing UI** may show Japanese and
other locales via message catalogs only (`installer-disclaimer-copy.md`).

## Decision

1. **Binding spec:** [`docs/development/i18n.md`](../development/i18n.md).

2. **Implementation home:** `frontend/src/shared/i18n/` — same architecture as
   nene-records (`en.ts` source of truth, `Partial<MessageCatalog>` locales,
   `I18nProvider`, `useTranslation()`, `translate()` with `{{param}}` interpolation).

3. **Hard rule:** No user-facing literals in `features/`, `pages/`, or `shared/ui`
   components — only `t('message.key')`.

4. **Release gate:** `ja.ts` must contain **every** key from `en.ts` (enforced by
   Vitest in `locales.test.ts`). Other locales may be Partial with English fallback
   until translated.

5. **Persistence:** `localStorage` key `nene-suite-locale`; `document.documentElement.lang`
   updated on switch.

6. **API boundary unchanged:** Problem Details from API stay English; UI maps to
   localized messages via `mapProblemDetailsToMessageKey`.

## Consequences

**Benefits**

- Instant locale switch without reload; one place to edit copy.
- Type-safe keys; missing keys caught in CI.
- Aligns with nene-records / NENE2 portfolio UX.

**Costs**

- Every UI PR touches message files; ja translation required alongside en.
- Slightly more boilerplate than inline strings.

**Follow-up**

- ESLint `no-hardcoded-ui-strings` rule when full frontend ESLint lands.
- Storybook locale toolbar via `withI18n` decorator.

## Related

- Issue: #16
- nene-records `frontend/src/shared/i18n/`
- [`docs/development/frontend-standards.md`](../development/frontend-standards.md)

/** Known product-mark slugs served from /logos/{slug}.svg. */
export const APP_LOGO_SLUGS = [
  'invoice',
  'deal',
  'records',
  'profile',
  'vault',
  'clear',
  'corpus',
  'concierge',
  'suite',
] as const

export type AppLogoSlug = (typeof APP_LOGO_SLUGS)[number]

export function isAppLogoSlug(value: string): value is AppLogoSlug {
  return (APP_LOGO_SLUGS as readonly string[]).includes(value)
}

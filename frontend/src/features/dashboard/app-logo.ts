import { isAppLogoSlug, type AppLogoSlug } from '@/shared/ui'

/** Maps an installed app's catalogId (e.g. "nene-invoice") to a logo slug, if one exists. */
export function catalogIdToLogoSlug(catalogId: string): AppLogoSlug | null {
  const slug = catalogId.replace(/^nene-/, '')
  return isAppLogoSlug(slug) ? slug : null
}

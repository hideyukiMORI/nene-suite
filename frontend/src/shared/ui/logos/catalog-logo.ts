import { isAppLogoSlug, type AppLogoSlug } from './app-logo-slugs'

/** Maps a catalog/installed app id (e.g. "nene-invoice") to a logo slug, if one exists. */
export function catalogIdToLogoSlug(catalogId: string): AppLogoSlug | null {
  const slug = catalogId.replace(/^nene-/, '')
  return isAppLogoSlug(slug) ? slug : null
}

import type { AppLogoSlug } from './app-logo-slugs'

/**
 * Per-app product mark. These carry each app's own brand color (the only place
 * app color appears — never reused in UI chrome, per DESIGN-SYSTEM.md §1.3), so
 * they ship as static colored SVGs served from /logos and rendered as <img>.
 */
export function AppLogo({
  slug,
  size = 48,
  alt = '',
}: {
  slug: AppLogoSlug
  size?: number
  alt?: string
}) {
  return (
    <img
      src={`/logos/${slug}.svg`}
      width={size}
      height={size}
      alt={alt}
      style={{
        display: 'block',
        flex: 'none',
        boxShadow: 'var(--shadow)',
        borderRadius: 'var(--r-sm)',
      }}
    />
  )
}

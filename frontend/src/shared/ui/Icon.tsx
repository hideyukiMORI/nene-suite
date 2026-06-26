import type { CSSProperties } from 'react'

interface IconProps {
  /** Material Symbols Rounded ligature name, e.g. "search", "notifications". */
  name: string
  /** Rendered glyph size in px (font-size). */
  size?: number
  /** Solid (filled) variant. */
  fill?: boolean
  /** Variable font weight axis (300–700). */
  weight?: number
  /** CSS color value — defaults to currentColor (inherits text color). */
  color?: string
  /** Optional extra class (e.g. a CSS-module lookup, which is `string | undefined`). */
  className?: string | undefined
}

/**
 * Material Symbols Rounded glyph. Decorative by default (aria-hidden) — pair an
 * accessible label on the interactive parent (button/link). Presentational
 * primitive: encapsulates the font-variation plumbing so features never set it.
 */
export function Icon({ name, size = 20, fill = false, weight = 320, color, className }: IconProps) {
  const style: CSSProperties = {
    fontSize: `${String(size)}px`,
    fontVariationSettings: `'FILL' ${fill ? '1' : '0'}, 'wght' ${String(weight)}`,
    ...(color !== undefined ? { color } : {}),
  }
  return (
    <span className={className === undefined ? 'ms' : `ms ${className}`} style={style} aria-hidden>
      {name}
    </span>
  )
}

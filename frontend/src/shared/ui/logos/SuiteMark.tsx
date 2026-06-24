/**
 * NeNe Suite brand mark — 4 squares + a central accent. Rendered as inline SVG
 * so it inherits the theme tokens (--brand-deep base, --logo-ring edge, --brand
 * accent square), unlike the colored per-app marks. See handoff/spec.html.
 */
export function SuiteMark({ size = 28, title }: { size?: number; title?: string }) {
  return (
    <svg
      viewBox="0 0 120 120"
      width={size}
      height={size}
      style={{ display: 'block', flex: 'none' }}
      xmlns="http://www.w3.org/2000/svg"
      role={title === undefined ? 'presentation' : 'img'}
      aria-label={title}
      aria-hidden={title === undefined}
    >
      <rect width="120" height="120" rx="27" fill="var(--brand-deep)" />
      <rect
        x="1"
        y="1"
        width="118"
        height="118"
        rx="26"
        fill="none"
        stroke="var(--logo-ring)"
        strokeWidth="2"
      />
      <g fill="#ffffff" opacity="0.92">
        <rect x="47" y="17" width="26" height="26" rx="7" />
        <rect x="17" y="47" width="26" height="26" rx="7" />
        <rect x="77" y="47" width="26" height="26" rx="7" />
        <rect x="47" y="77" width="26" height="26" rx="7" />
      </g>
      <rect x="47" y="47" width="26" height="26" rx="7" fill="var(--brand)" />
    </svg>
  )
}

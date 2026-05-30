import type { ReactNode } from 'react'

/**
 * Presentational async-state primitives. Callers pass already-localized strings
 * (shared/ui stays presentation-only — no data or i18n lookups required here).
 */

export function LoadingState({ label }: { label: string }) {
  return <p aria-busy="true">{label}</p>
}

export function ErrorState({ label }: { label: string }) {
  return <p role="alert">{label}</p>
}

interface EmptyStateProps {
  title: string
  description?: string
  action?: ReactNode
}

export function EmptyState({ title, description, action }: EmptyStateProps) {
  return (
    <section>
      <h3>{title}</h3>
      {description !== undefined ? <p>{description}</p> : null}
      {action}
    </section>
  )
}

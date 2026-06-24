import type { ReactNode } from 'react'
import { Icon } from '../Icon'
import styles from './PlaceholderState.module.css'

interface PlaceholderStateProps {
  /** Material Symbols ligature name. */
  icon: string
  title: string
  description?: string
  action?: ReactNode
}

/**
 * Dashed-border empty/placeholder panel (DESIGN-SYSTEM.md §6). Used both for
 * genuine empty states and for surfaces whose backend lands in a later phase —
 * an honest "not built yet", never fabricated data.
 */
export function PlaceholderState({ icon, title, description, action }: PlaceholderStateProps) {
  return (
    <section className={styles['root']}>
      <span className={styles['icon']}>
        <Icon name={icon} size={32} />
      </span>
      <h3 className={styles['title']}>{title}</h3>
      {description !== undefined ? <p className={styles['description']}>{description}</p> : null}
      {action}
    </section>
  )
}

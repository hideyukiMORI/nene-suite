import type { ReactNode } from 'react'
import { Icon } from '@/shared/ui'
import type { CalloutTone } from '../content'
import styles from './callout.module.css'

interface ToneMeta {
  readonly icon: string
  readonly color: string
  /** Screen-reader-only severity prefix (severity is not conveyed by color alone). */
  readonly srLabel: string
}

const TONE: Readonly<Record<CalloutTone, ToneMeta>> = {
  danger: { icon: 'error', color: 'var(--danger)', srLabel: '重要: ' },
  warning: { icon: 'warning', color: 'var(--warn)', srLabel: '注意: ' },
  neutral: { icon: 'info', color: 'var(--fg-3)', srLabel: '' },
}

/** Highlighted callout for invariants / warnings inside guides. */
export function Callout({
  tone,
  children,
}: {
  readonly tone: CalloutTone
  readonly children: ReactNode
}): ReactNode {
  const meta = TONE[tone]
  return (
    <div className={styles['root']} data-tone={tone} role={tone === 'danger' ? 'alert' : 'note'}>
      <Icon name={meta.icon} size={19} fill color={meta.color} className={styles['icon'] ?? ''} />
      <p className={styles['text']}>
        {meta.srLabel !== '' ? <span className={styles['srOnly']}>{meta.srLabel}</span> : null}
        {children}
      </p>
    </div>
  )
}

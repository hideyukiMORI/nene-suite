import type { ReactNode } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import type { CalloutTone } from '../content'
import styles from './callout.module.css'

interface ToneMeta {
  readonly icon: string
  readonly color: string
  /** Screen-reader-only severity prefix (severity is not conveyed by color alone). */
  readonly srLabelKey: MessageKey | null
}

const TONE: Readonly<Record<CalloutTone, ToneMeta>> = {
  danger: { icon: 'error', color: 'var(--danger)', srLabelKey: 'suite.help.callout.danger' },
  warning: { icon: 'warning', color: 'var(--warn)', srLabelKey: 'suite.help.callout.warning' },
  neutral: { icon: 'info', color: 'var(--fg-3)', srLabelKey: null },
}

/** Highlighted callout for invariants / warnings inside guides. */
export function Callout({
  tone,
  children,
}: {
  readonly tone: CalloutTone
  readonly children: ReactNode
}): ReactNode {
  const { t } = useTranslation()
  const meta = TONE[tone]
  return (
    <div className={styles['root']} data-tone={tone} role={tone === 'danger' ? 'alert' : 'note'}>
      <Icon name={meta.icon} size={19} fill color={meta.color} className={styles['icon'] ?? ''} />
      <p className={styles['text']}>
        {meta.srLabelKey !== null ? (
          <span className={styles['srOnly']}>{t(meta.srLabelKey)}</span>
        ) : null}
        {children}
      </p>
    </div>
  )
}

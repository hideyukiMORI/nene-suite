import type { ReactNode } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import type { DiffResult, DiffStatus, DiffValue } from '../lib/audit-diff'
import styles from './audit-diff.module.css'

const SIGN: Readonly<Record<DiffStatus, string>> = {
  added: '＋',
  removed: '−',
  changed: '～',
  same: '=',
}

const STATUS_LABEL: Readonly<Record<Exclude<DiffStatus, 'same'>, MessageKey>> = {
  added: 'suite.audit.diff.added',
  removed: 'suite.audit.diff.removed',
  changed: 'suite.audit.diff.changed',
}

interface AuditDiffProps {
  readonly result: DiffResult
  readonly view: 'unified' | 'split'
  readonly showAll: boolean
}

export function AuditDiff({ result, view, showAll }: AuditDiffProps): ReactNode {
  const { t } = useTranslation()
  const rows = showAll ? result.rows : result.rows.filter((row) => row.status !== 'same')

  function cellTone(value: DiffValue, status: DiffStatus, side: 'before' | 'after'): string {
    if (!value.present) return 'empty'
    if (value.redacted) return 'redacted'
    if (side === 'before')
      return status === 'removed' || status === 'changed' ? 'remove' : 'neutral'
    return status === 'added' || status === 'changed' ? 'add' : 'neutral'
  }

  function renderCell(value: DiffValue, status: DiffStatus, side: 'before' | 'after'): ReactNode {
    const tone = cellTone(value, status, side)
    return (
      <span className={styles['cell']} data-tone={tone}>
        {value.present ? value.text : t('suite.audit.diff.none')}
      </span>
    )
  }

  function renderTag(status: DiffStatus): ReactNode {
    if (status === 'same') return null
    return (
      <span className={styles['tag']} data-status={status}>
        {SIGN[status]} {t(STATUS_LABEL[status])}
      </span>
    )
  }

  return (
    <div className={styles['root']}>
      {rows.map((row) => {
        if (row.kind === 'container') {
          return (
            <div
              key={`${String(row.depth)}-${row.key}-c`}
              className={styles['container']}
              style={{ marginLeft: row.depth * 16 }}
            >
              <Icon
                name={row.isArray ? 'data_array' : 'data_object'}
                size={16}
                color="var(--fg-3)"
              />
              <span className={styles['containerKey']}>{row.key}</span>
              <span className={styles['glyph']}>{row.isArray ? '[ ]' : '{ }'}</span>
              {renderTag(row.status)}
            </div>
          )
        }
        return (
          <div
            key={`${String(row.depth)}-${row.key}-l`}
            className={styles['leaf']}
            data-status={row.status}
            style={{ marginLeft: row.depth * 16 }}
          >
            <div className={styles['leafHead']}>
              {renderTag(row.status)}
              <span className={styles['leafKey']}>{row.key}</span>
            </div>
            {view === 'split' ? (
              <div className={styles['split']}>
                {renderCell(row.before, row.status, 'before')}
                {renderCell(row.after, row.status, 'after')}
              </div>
            ) : (
              <div className={styles['unified']}>
                {(row.status === 'removed' || row.status === 'changed') && (
                  <div className={styles['uniRow']}>
                    <span className={styles['uniSign']} data-sign="removed">
                      −
                    </span>
                    {renderCell(row.before, row.status, 'before')}
                  </div>
                )}
                {(row.status === 'added' || row.status === 'changed' || row.status === 'same') && (
                  <div className={styles['uniRow']}>
                    <span
                      className={styles['uniSign']}
                      data-sign={row.status === 'same' ? 'same' : 'added'}
                    >
                      {row.status === 'same' ? '=' : '+'}
                    </span>
                    {renderCell(row.after, row.status, 'after')}
                  </div>
                )}
              </div>
            )}
          </div>
        )
      })}
    </div>
  )
}

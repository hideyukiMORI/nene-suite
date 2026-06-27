import { useEffect, useMemo, useState, type ReactNode } from 'react'
import type { SuiteAuditEvent } from '@/entities/suite-audit-event'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { buildDiff } from '../lib/audit-diff'
import {
  CHANGE_ICON,
  absoluteTime,
  actionGloss,
  classifyChange,
  entityIcon,
  relativeTime,
} from '../lib/audit-format'
import { AuditDiff } from './AuditDiff'
import styles from './audit-detail.module.css'

const CHANGE_LABEL = {
  create: 'suite.audit.change.created',
  update: 'suite.audit.change.updated',
  delete: 'suite.audit.change.deleted',
} as const satisfies Record<string, MessageKey>

interface MetaRow {
  readonly label: MessageKey
  readonly value: string
  readonly note?: string
}

function formatMetaValue(value: unknown): string {
  if (value === null) return 'null'
  if (typeof value === 'string') return value
  if (typeof value === 'number' || typeof value === 'boolean') return String(value)
  return JSON.stringify(value)
}

interface AuditDetailDrawerProps {
  readonly event: SuiteAuditEvent
  readonly onClose: () => void
}

export function AuditDetailDrawer({ event, onClose }: AuditDetailDrawerProps): ReactNode {
  const { t, locale } = useTranslation()
  const [view, setView] = useState<'unified' | 'split'>('unified')
  const [showAll, setShowAll] = useState(false)

  useEffect(() => {
    function onKey(keyEvent: KeyboardEvent): void {
      if (keyEvent.key === 'Escape') onClose()
    }
    document.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('keydown', onKey)
    }
  }, [onClose])

  const diff = useMemo(() => buildDiff(event.before, event.after), [event])
  const kind = classifyChange(event)
  const gloss = actionGloss(event.action, locale)
  const machine = event.actorUserId === null

  const meta: readonly MetaRow[] = [
    {
      label: 'suite.audit.meta.actor',
      value: event.actorLabel ?? '—',
      note: machine ? t('suite.audit.actor.machine') : t('suite.audit.actor.human'),
    },
    { label: 'suite.audit.meta.type', value: event.entityType },
    { label: 'suite.audit.meta.target', value: event.entityId },
    {
      label: 'suite.audit.meta.time',
      value: absoluteTime(event.createdAt),
      note: relativeTime(event.createdAt, locale),
    },
    { label: 'suite.audit.meta.source', value: event.source },
    { label: 'suite.audit.meta.request', value: event.requestId ?? '—' },
    { label: 'suite.audit.meta.session', value: event.installSessionId ?? '—' },
    { label: 'suite.audit.meta.org', value: event.orgExternalId ?? '—' },
    { label: 'suite.audit.meta.suite', value: event.suiteId },
  ]

  const metadataEntries = event.metadata !== null ? Object.entries(event.metadata) : []
  const summary = (
    [
      { status: 'added', count: diff.added, label: 'suite.audit.diff.added', sign: '＋' },
      { status: 'changed', count: diff.changed, label: 'suite.audit.diff.changed', sign: '～' },
      { status: 'removed', count: diff.removed, label: 'suite.audit.diff.removed', sign: '−' },
    ] as const
  ).filter((entry) => entry.count > 0)
  const noChange = diff.added + diff.changed + diff.removed === 0

  return (
    <>
      <button
        type="button"
        className={styles['scrim']}
        aria-label={t('common.actions.close')}
        onClick={onClose}
      />
      <aside className={styles['drawer']} aria-label={t('suite.audit.detail.title')}>
        <div className={styles['head']}>
          <span className={styles['headIcon']}>
            <Icon name={entityIcon(event.entityType)} size={22} />
          </span>
          <div className={styles['headMain']}>
            <div className={styles['headTop']}>
              <span className={styles['badge']} data-kind={kind}>
                <Icon name={CHANGE_ICON[kind]} size={14} />
                {t(CHANGE_LABEL[kind])}
              </span>
              <span className={styles['kicker']}>
                {event.entityType} · {event.source}
              </span>
            </div>
            <h2 className={styles['title']}>{event.action}</h2>
            {gloss !== null ? <p className={styles['gloss']}>{gloss}</p> : null}
          </div>
          <button
            type="button"
            className={styles['close']}
            aria-label={t('common.actions.close')}
            onClick={onClose}
          >
            <Icon name="close" size={20} />
          </button>
        </div>

        <div className={styles['body']}>
          <dl className={styles['meta']}>
            {meta.map((row) => (
              <div key={row.label} className={styles['metaCell']}>
                <dt className={styles['metaLabel']}>{t(row.label)}</dt>
                <dd className={styles['metaValue']}>
                  <span>{row.value}</span>
                  {row.note !== undefined ? (
                    <span className={styles['metaNote']}>{row.note}</span>
                  ) : null}
                </dd>
              </div>
            ))}
          </dl>

          {metadataEntries.length > 0 ? (
            <section className={styles['section']}>
              <p className={styles['sectionHead']}>
                <Icon name="data_object" size={17} color="var(--fg-3)" />
                {t('suite.audit.metadata')}
              </p>
              <div className={styles['metadataList']}>
                {metadataEntries.map(([name, value]) => (
                  <div key={name} className={styles['metadataItem']}>
                    <span className={styles['metadataName']}>{name}</span>
                    <span
                      className={styles['metadataValue']}
                      data-redacted={value === '[REDACTED]' ? 'true' : 'false'}
                    >
                      {formatMetaValue(value)}
                    </span>
                  </div>
                ))}
              </div>
            </section>
          ) : null}

          <div className={styles['diffHead']}>
            <p className={styles['sectionHead']}>
              <Icon name="difference" size={17} color="var(--fg-3)" />
              {t('suite.audit.diff.title')}
              <span className={styles['diffArrow']}>before → after</span>
            </p>
            <div
              className={styles['viewToggle']}
              role="group"
              aria-label={t('suite.audit.diff.title')}
            >
              <button
                type="button"
                className={styles['viewBtn']}
                data-active={view === 'unified'}
                onClick={() => {
                  setView('unified')
                }}
              >
                {t('suite.audit.diff.unified')}
              </button>
              <button
                type="button"
                className={styles['viewBtn']}
                data-active={view === 'split'}
                onClick={() => {
                  setView('split')
                }}
              >
                {t('suite.audit.diff.split')}
              </button>
            </div>
          </div>

          <div className={styles['summaryRow']}>
            {summary.map((entry) => (
              <span key={entry.status} className={styles['summaryChip']} data-status={entry.status}>
                {entry.sign} {entry.count} {t(entry.label)}
              </span>
            ))}
            <span className={styles['summarySpacer']} />
            {diff.same > 0 ? (
              <button
                type="button"
                className={styles['toggleUnchanged']}
                onClick={() => {
                  setShowAll((current) => !current)
                }}
              >
                <Icon name={showAll ? 'visibility_off' : 'visibility'} size={15} />
                {showAll
                  ? t('suite.audit.diff.onlyChanged')
                  : t('suite.audit.diff.unchanged', { count: diff.same })}
              </button>
            ) : null}
          </div>

          {kind === 'create' ? (
            <div className={styles['banner']} data-kind="create">
              <Icon name="add_circle" size={17} color="var(--ok)" />
              {t('suite.audit.diff.bannerCreate')}
            </div>
          ) : null}
          {kind === 'delete' ? (
            <div className={styles['banner']} data-kind="delete">
              <Icon name="remove_circle" size={17} color="var(--danger)" />
              {t('suite.audit.diff.bannerDelete')}
            </div>
          ) : null}

          {view === 'split' && !noChange ? (
            <div className={styles['splitHead']}>
              <span>{t('suite.audit.diff.before')}</span>
              <span>{t('suite.audit.diff.after')}</span>
            </div>
          ) : null}

          {noChange ? (
            <div className={styles['noChange']}>
              <Icon name="check_circle" size={18} />
              {t('suite.audit.diff.noChange')}
            </div>
          ) : (
            <AuditDiff result={diff} view={view} showAll={showAll} />
          )}
        </div>
      </aside>
    </>
  )
}

import { useMemo, useState, type ReactNode } from 'react'
import type { SuiteAuditEvent } from '@/entities/suite-audit-event'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { buildAuditCsv } from '../csv'
import { useAuditViewer } from '../hooks/use-audit-viewer'
import {
  CHANGE_ICON,
  absoluteTime,
  actionGloss,
  classifyChange,
  entityIcon,
  relativeTime,
  shortId,
  sourceIcon,
  type ChangeKind,
} from '../lib/audit-format'
import { AuditDetailDrawer } from './AuditDetailDrawer'
import styles from './audit-viewer.module.css'

const CHANGE_LABEL = {
  create: 'suite.audit.change.created',
  update: 'suite.audit.change.updated',
  delete: 'suite.audit.change.deleted',
} as const satisfies Record<ChangeKind, MessageKey>

const CHANGE_FILTERS = [
  { value: 'all', label: 'suite.audit.change.all', sign: '' },
  { value: 'create', label: 'suite.audit.change.created', sign: '＋' },
  { value: 'update', label: 'suite.audit.change.updated', sign: '～' },
  { value: 'delete', label: 'suite.audit.change.deleted', sign: '−' },
] as const satisfies readonly { value: ChangeKind | 'all'; label: MessageKey; sign: string }[]

export function AuditViewer(): ReactNode {
  const { t } = useTranslation()
  const { events, isLoading, isError, hasMore, isLoadingMore, loadMore, refetch } = useAuditViewer()
  const [query, setQuery] = useState('')
  const [sourceFilter, setSourceFilter] = useState('all')
  const [changeFilter, setChangeFilter] = useState<ChangeKind | 'all'>('all')
  const [selectedId, setSelectedId] = useState<string | null>(null)

  const sources = useMemo(() => [...new Set(events.map((event) => event.source))].sort(), [events])

  const needle = query.trim().toLowerCase()
  const visible = events.filter((event) => {
    if (sourceFilter !== 'all' && event.source !== sourceFilter) return false
    if (changeFilter !== 'all' && classifyChange(event) !== changeFilter) return false
    if (needle !== '') {
      const hay =
        `${event.action} ${event.actorLabel ?? ''} ${event.entityType} ${event.entityId} ${event.source}`.toLowerCase()
      if (!hay.includes(needle)) return false
    }
    return true
  })

  const selected =
    selectedId !== null ? (events.find((event) => event.id === selectedId) ?? null) : null

  const exportCsv = (): void => {
    if (typeof URL.createObjectURL !== 'function') return
    const blob = new Blob([buildAuditCsv(visible)], { type: 'text/csv;charset=utf-8' })
    const url = URL.createObjectURL(blob)
    const anchor = document.createElement('a')
    anchor.href = url
    anchor.download = 'suite-audit.csv'
    anchor.click()
    URL.revokeObjectURL(url)
  }

  const ready = !isLoading && !isError

  return (
    <div className={styles['root']}>
      <div className={styles['headerRow']}>
        <div>
          <h1 className={styles['title']}>{t('suite.audit.title')}</h1>
          <p className={styles['subtitle']}>{t('suite.audit.description')}</p>
        </div>
        <div className={styles['headerActions']}>
          <span className={styles['appendOnly']}>
            <Icon name="circle" size={12} color="var(--color-success)" />
            {t('suite.audit.appendOnly')}
          </span>
          {ready && events.length > 0 ? (
            <button type="button" className={styles['exportBtn']} onClick={exportCsv}>
              <Icon name="download" size={18} />
              {t('suite.audit.exportCsv')}
            </button>
          ) : null}
        </div>
      </div>

      {isLoading ? <p className={styles['muted']}>{t('common.state.loading')}</p> : null}

      {isError ? (
        <div className={styles['errorPanel']} role="alert">
          <Icon name="error" size={40} fill color="var(--color-danger)" />
          <p>{t('common.state.error')}</p>
          <button type="button" className={styles['retryBtn']} onClick={refetch}>
            <Icon name="refresh" size={18} />
            {t('common.actions.retry')}
          </button>
        </div>
      ) : null}

      {ready && events.length === 0 ? (
        <p className={styles['muted']}>{t('suite.audit.empty')}</p>
      ) : null}

      {ready && events.length > 0 ? (
        <>
          <div className={styles['toolbar']}>
            <label className={styles['search']}>
              <Icon name="search" size={18} color="var(--color-text-faint)" />
              <input
                type="search"
                value={query}
                placeholder={t('suite.audit.searchPlaceholder')}
                aria-label={t('suite.audit.searchPlaceholder')}
                onChange={(event) => {
                  setQuery(event.target.value)
                }}
              />
            </label>

            <div className={styles['selectWrap']}>
              <select
                className={styles['select']}
                aria-label={t('suite.audit.meta.source')}
                value={sourceFilter}
                onChange={(event) => {
                  setSourceFilter(event.target.value)
                }}
              >
                <option value="all">{t('suite.audit.source.all')}</option>
                {sources.map((source) => (
                  <option key={source} value={source}>
                    {source}
                  </option>
                ))}
              </select>
              <Icon
                name="expand_more"
                size={18}
                color="var(--color-text-faint)"
                className={styles['selectChevron'] ?? ''}
              />
            </div>

            <div
              className={styles['changeTabs']}
              role="tablist"
              aria-label={t('suite.audit.column.change')}
            >
              {CHANGE_FILTERS.map((filter) => (
                <button
                  key={filter.value}
                  type="button"
                  role="tab"
                  aria-selected={changeFilter === filter.value}
                  className={styles['changeTab']}
                  data-active={changeFilter === filter.value}
                  onClick={() => {
                    setChangeFilter(filter.value)
                  }}
                >
                  {filter.sign !== '' ? (
                    <span className={styles['changeTabSign']} data-kind={filter.value}>
                      {filter.sign}
                    </span>
                  ) : null}
                  {t(filter.label)}
                </button>
              ))}
            </div>

            <span className={styles['count']}>
              <b>{visible.length}</b> {t('suite.audit.events')}
            </span>
          </div>

          <div className={styles['table']}>
            <div className={styles['headRow']}>
              <span>{t('suite.audit.column.action')}</span>
              <span>{t('suite.audit.column.change')}</span>
              <span>{t('suite.audit.column.entity')}</span>
              <span>{t('suite.audit.column.actor')}</span>
              <span>{t('suite.audit.column.time')}</span>
            </div>
            {visible.length === 0 ? (
              <div className={styles['empty']}>
                <Icon name="search_off" size={32} color="var(--color-text-faint)" />
                <span>{t('suite.audit.noMatch')}</span>
              </div>
            ) : (
              visible.map((event) => (
                <AuditRow
                  key={event.id}
                  event={event}
                  selected={selectedId === event.id}
                  onOpen={() => {
                    setSelectedId(event.id)
                  }}
                />
              ))
            )}
          </div>

          {hasMore ? (
            <button
              type="button"
              className={styles['loadMore']}
              disabled={isLoadingMore}
              onClick={loadMore}
            >
              {t('suite.audit.loadMore')}
            </button>
          ) : null}

          <p className={styles['hint']}>{t('suite.audit.hint')}</p>
        </>
      ) : null}

      {selected !== null ? (
        <AuditDetailDrawer
          event={selected}
          onClose={() => {
            setSelectedId(null)
          }}
        />
      ) : null}
    </div>
  )
}

interface AuditRowProps {
  readonly event: SuiteAuditEvent
  readonly selected: boolean
  readonly onOpen: () => void
}

function AuditRow({ event, selected, onOpen }: AuditRowProps): ReactNode {
  const { t, locale } = useTranslation()
  const kind = classifyChange(event)
  const gloss = actionGloss(event.action, locale)
  const machine = event.actorUserId === null

  return (
    <div
      role="button"
      tabIndex={0}
      className={styles['row']}
      data-selected={selected}
      onClick={onOpen}
      onKeyDown={(keyEvent) => {
        if (keyEvent.key === 'Enter' || keyEvent.key === ' ') {
          keyEvent.preventDefault()
          onOpen()
        }
      }}
    >
      <div className={styles['cellAction']}>
        <span className={styles['actionIcon']}>
          <Icon name={entityIcon(event.entityType)} size={18} />
        </span>
        <span className={styles['actionText']}>
          <span className={styles['actionName']}>{event.action}</span>
          {gloss !== null ? <span className={styles['actionGloss']}>{gloss}</span> : null}
        </span>
      </div>

      <span>
        <span className={styles['changeBadge']} data-kind={kind}>
          <Icon name={CHANGE_ICON[kind]} size={14} />
          {t(CHANGE_LABEL[kind])}
        </span>
      </span>

      <span className={styles['cellEntity']}>
        <span className={styles['entityType']}>{event.entityType}</span>
        <span className={styles['entityId']} title={event.entityId}>
          {shortId(event.entityId)}
        </span>
      </span>

      <div className={styles['cellActor']}>
        <Icon name={machine ? 'smart_toy' : 'person'} size={18} color="var(--color-text-faint)" />
        <span className={styles['actorText']}>
          <span className={styles['actorLabel']}>{event.actorLabel ?? '—'}</span>
          <span className={styles['sourcePill']}>
            <Icon name={sourceIcon(event.source)} size={12} />
            {event.source}
          </span>
        </span>
      </div>

      <span className={styles['cellTime']}>
        <span className={styles['timeRel']}>{relativeTime(event.createdAt, locale)}</span>
        <span className={styles['timeAbs']}>{absoluteTime(event.createdAt)}</span>
      </span>
    </div>
  )
}

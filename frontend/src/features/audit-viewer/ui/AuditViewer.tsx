import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { buildAuditCsv } from '../csv'
import { useAuditViewer } from '../hooks/use-audit-viewer'
import styles from './audit-viewer.module.css'

const KIND_PALETTE = ['var(--accent)', 'var(--ok)', 'var(--warn)', 'var(--origin)', 'var(--danger)']

function actorOf(event: { actorLabel: string | null; source: string }): string {
  return event.actorLabel ?? event.source
}

export function AuditViewer() {
  const { t } = useTranslation()
  const { events, isLoading, isError, hasMore, isLoadingMore, loadMore, refetch } = useAuditViewer()
  const [actorFilter, setActorFilter] = useState('all')
  const [kindFilter, setKindFilter] = useState('all')

  const actors = [...new Set(events.map(actorOf))]
  const kinds = [...new Set(events.map((event) => event.entityType))].sort()
  const kindColor = (kind: string): string =>
    KIND_PALETTE[kinds.indexOf(kind) % KIND_PALETTE.length] ?? 'var(--accent)'

  const hasFilter = actorFilter !== 'all' || kindFilter !== 'all'
  const visible = events.filter(
    (event) =>
      (actorFilter === 'all' || actorOf(event) === actorFilter) &&
      (kindFilter === 'all' || event.entityType === kindFilter),
  )

  const clearFilters = (): void => {
    setActorFilter('all')
    setKindFilter('all')
  }

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
        {ready && events.length > 0 ? (
          <button type="button" className={styles['exportBtn']} onClick={exportCsv}>
            <Icon name="download" size={19} />
            {t('suite.audit.exportCsv')}
          </button>
        ) : null}
      </div>

      {isLoading ? <p className={styles['muted']}>{t('common.state.loading')}</p> : null}

      {isError ? (
        <div className={styles['errorPanel']} role="alert">
          <Icon name="error" size={40} fill color="var(--danger)" />
          <p>{t('common.state.error')}</p>
          <button type="button" className={styles['retryBtn']} onClick={refetch}>
            <Icon name="refresh" size={18} />
            {t('common.actions.retry')}
          </button>
        </div>
      ) : null}

      {ready ? (
        <>
          {events.length > 0 ? (
            <div className={styles['filters']}>
              <div className={styles['selectWrap']}>
                <Icon
                  name="person"
                  size={18}
                  color="var(--fg-3)"
                  className={styles['selectIcon'] ?? ''}
                />
                <select
                  className={styles['select']}
                  aria-label={t('suite.audit.column.actor')}
                  value={actorFilter}
                  onChange={(event) => {
                    setActorFilter(event.target.value)
                  }}
                >
                  <option value="all">{t('suite.audit.filter.allActors')}</option>
                  {actors.map((actor) => (
                    <option key={actor} value={actor}>
                      {actor}
                    </option>
                  ))}
                </select>
              </div>
              <div className={styles['chips']}>
                {kinds.map((kind) => (
                  <button
                    key={kind}
                    type="button"
                    className={styles['chip']}
                    data-active={kindFilter === kind}
                    onClick={() => {
                      setKindFilter((current) => (current === kind ? 'all' : kind))
                    }}
                  >
                    {kind}
                  </button>
                ))}
              </div>
              {hasFilter ? (
                <button type="button" className={styles['clearBtn']} onClick={clearFilters}>
                  <Icon name="close" size={17} />
                  {t('suite.audit.clearFilters')}
                </button>
              ) : null}
            </div>
          ) : null}

          {events.length === 0 ? (
            <p className={styles['muted']}>{t('suite.audit.empty')}</p>
          ) : visible.length === 0 ? (
            <div className={styles['empty']}>
              <Icon name="filter_alt_off" size={40} color="var(--fg-3)" />
              <p>{t('suite.audit.noMatch')}</p>
              <button type="button" className={styles['clearBtn']} onClick={clearFilters}>
                {t('suite.audit.clearFilters')}
              </button>
            </div>
          ) : (
            <>
              <div className={styles['table']}>
                <div className={styles['headRow']}>
                  <span>{t('suite.audit.column.actor')}</span>
                  <span>{t('suite.audit.column.action')}</span>
                  <span>{t('suite.audit.column.entity')}</span>
                  <span>{t('suite.audit.column.time')}</span>
                </div>
                {visible.map((event) => (
                  <div key={event.id} className={styles['row']}>
                    <span className={styles['actor']}>{actorOf(event)}</span>
                    <span className={styles['action']}>
                      <span
                        className={styles['dot']}
                        style={{ background: kindColor(event.entityType) }}
                      />
                      {event.action}
                    </span>
                    <span className={styles['entity']}>{event.entityType}</span>
                    <span className={styles['time']}>{event.createdAt}</span>
                  </div>
                ))}
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
            </>
          )}
        </>
      ) : null}
    </div>
  )
}

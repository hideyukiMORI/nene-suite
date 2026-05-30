import { useTranslation } from '@/shared/i18n'
import { ErrorState, LoadingState } from '@/shared/ui'
import { useAuditViewer } from '../hooks/use-audit-viewer'

export function AuditEventsTable() {
  const { t } = useTranslation()
  const { events, isLoading, isError, hasMore, isLoadingMore, loadMore } = useAuditViewer()

  if (isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }

  if (isError) {
    return <ErrorState label={t('common.state.error')} />
  }

  if (events.length === 0) {
    return <p>{t('suite.audit.empty')}</p>
  }

  return (
    <div>
      <table>
        <thead>
          <tr>
            <th>{t('suite.audit.column.action')}</th>
            <th>{t('suite.audit.column.actor')}</th>
            <th>{t('suite.audit.column.time')}</th>
          </tr>
        </thead>
        <tbody>
          {events.map((event) => (
            <tr key={event.id}>
              <td>{event.action}</td>
              <td>{event.actorLabel ?? event.source}</td>
              <td>{event.createdAt}</td>
            </tr>
          ))}
        </tbody>
      </table>

      {hasMore ? (
        <button type="button" disabled={isLoadingMore} onClick={loadMore}>
          {t('common.actions.next')}
        </button>
      ) : null}
    </div>
  )
}

import { useOperators } from '@/entities/operator'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './admin-hub.module.css'

/**
 * Operator roster. Email + name are real (listOperators); scope / MFA / last
 * access need a per-operator detail API (Phase B) and are shown as honest "—".
 */
export function OperatorsTab() {
  const { t } = useTranslation()
  const query = useOperators()
  const operators = query.data ?? []

  if (query.isLoading) {
    return <p className={styles['muted']}>{t('common.state.loading')}</p>
  }
  if (query.isError) {
    return (
      <div className={styles['errorPanel']} role="alert">
        <Icon name="error" size={40} fill color="var(--color-danger)" />
        <p>{t('common.state.error')}</p>
        <button
          type="button"
          className={styles['retryBtn']}
          onClick={() => {
            void query.refetch()
          }}
        >
          <Icon name="refresh" size={18} />
          {t('common.actions.retry')}
        </button>
      </div>
    )
  }
  if (operators.length === 0) {
    return <p className={styles['muted']}>{t('suite.admin.operators.empty')}</p>
  }

  const phaseB = t('suite.admin.operators.phaseB')

  return (
    <div className={styles['table']}>
      <div className={styles['headRow']}>
        <span>{t('suite.admin.operators.email')}</span>
        <span>{t('suite.admin.operators.name')}</span>
        <span>{t('suite.admin.operators.scope')}</span>
        <span>{t('suite.admin.operators.mfa')}</span>
        <span>{t('suite.admin.operators.lastAccess')}</span>
      </div>
      {operators.map((operator) => (
        <div key={operator.id} className={styles['row']}>
          <span className={styles['mono']}>{operator.email}</span>
          <span>{operator.displayName ?? '—'}</span>
          <span className={styles['muted']} title={phaseB}>
            —
          </span>
          <span className={styles['muted']} title={phaseB}>
            —
          </span>
          <span className={styles['muted']} title={phaseB}>
            —
          </span>
        </div>
      ))}
    </div>
  )
}

import { Link } from 'react-router-dom'
import { authStore } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './dashboard.module.css'

/** Masthead: eyebrow + greeting + CTA. */
export function Masthead() {
  const { t } = useTranslation()

  const session = authStore.getSession()
  const email = session?.operator.email ?? ''
  const name = session?.operator.displayName ?? (email.split('@')[0] || 'superadmin')

  return (
    <div className={styles['masthead']}>
      <div>
        <div className={styles['eyebrow']}>
          {t('suite.home.eyebrow')} · {t('suite.org.indicator.superadmin')}
        </div>
        <h1 className={styles['greeting']}>
          {t('suite.home.greeting')}
          <span className={styles['name']}>{name}</span>
        </h1>
        <p className={styles['subtitle']}>{t('suite.home.subtitle')}</p>
      </div>
      <Link to="/install" className={styles['getApps']}>
        <Icon name="apps" size={19} />
        {t('suite.nav.getApps')}
      </Link>
    </div>
  )
}

import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from '../install-wizard.module.css'

export function CompleteStep() {
  const { t } = useTranslation()

  return (
    <div className={styles['completeWrap']}>
      <span className={styles['completeIcon']}>
        <Icon name="check_circle" size={34} fill />
      </span>
      <h3 className={styles['stepTitle']}>{t('suite.install.complete.title')}</h3>
      <p className={styles['stepDesc']}>{t('suite.install.complete.description')}</p>
      <Link to="/" className={styles['completeLink']}>
        {t('suite.install.complete.goToLauncher')}
      </Link>
    </div>
  )
}

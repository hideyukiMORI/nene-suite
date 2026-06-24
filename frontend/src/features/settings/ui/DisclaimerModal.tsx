import { useTranslation } from '@/shared/i18n'
import { Modal } from '@/shared/ui'
import styles from './settings.module.css'

/** Binding disclaimer (ADR 0003). Reuses the approved disclaimer copy catalog. */
export function DisclaimerModal({ onClose }: { onClose: () => void }) {
  const { t } = useTranslation()
  return (
    <Modal
      onClose={onClose}
      ariaLabel={t('suite.disclaimer.title')}
      closeLabel={t('common.actions.close')}
    >
      <h2 className={styles['modalTitle']}>{t('suite.disclaimer.title')}</h2>
      <p className={styles['modalBody']}>{t('suite.disclaimer.shortNotice')}</p>
      <p className={styles['modalFooter']}>{t('suite.disclaimer.footer')}</p>
    </Modal>
  )
}

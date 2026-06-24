import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'
import styles from '../install-wizard.module.css'

interface DisclaimerStepProps {
  isPending: boolean
  onAccept: () => void
}

export function DisclaimerStep({ isPending, onAccept }: DisclaimerStepProps) {
  const { t } = useTranslation()
  const [agreed, setAgreed] = useState(false)

  return (
    <div>
      <h3 className={styles['stepTitle']}>{t('suite.disclaimer.title')}</h3>
      <div className={styles['noticeBox']}>{t('suite.disclaimer.shortNotice')}</div>

      <label className={styles['checkLabel']}>
        <input
          type="checkbox"
          checked={agreed}
          onChange={(event) => {
            setAgreed(event.target.checked)
          }}
        />
        {t('suite.disclaimer.checkbox')}
      </label>

      {!agreed ? <p className={styles['mustAccept']}>{t('suite.disclaimer.mustAccept')}</p> : null}

      <div className={styles['actions']}>
        <button
          type="button"
          className={styles['primaryBtn']}
          disabled={isPending || !agreed}
          onClick={onAccept}
        >
          {t('common.actions.confirm')}
        </button>
      </div>
    </div>
  )
}

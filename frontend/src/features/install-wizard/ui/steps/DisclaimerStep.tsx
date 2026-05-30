import { useState } from 'react'
import { useTranslation } from '@/shared/i18n'

interface DisclaimerStepProps {
  isPending: boolean
  onAccept: () => void
}

export function DisclaimerStep({ isPending, onAccept }: DisclaimerStepProps) {
  const { t } = useTranslation()
  const [agreed, setAgreed] = useState(false)

  return (
    <section>
      <h3>{t('suite.disclaimer.title')}</h3>
      <p>{t('suite.disclaimer.shortNotice')}</p>

      <label>
        <input
          type="checkbox"
          checked={agreed}
          onChange={(event) => {
            setAgreed(event.target.checked)
          }}
        />
        {t('suite.disclaimer.checkbox')}
      </label>

      {!agreed ? <p>{t('suite.disclaimer.mustAccept')}</p> : null}

      <button type="button" disabled={isPending || !agreed} onClick={onAccept}>
        {t('common.actions.confirm')}
      </button>
    </section>
  )
}

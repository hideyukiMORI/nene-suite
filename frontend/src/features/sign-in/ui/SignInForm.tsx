import { useForm } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { useSignIn, type SignInCredentials } from '../model/use-sign-in'
import styles from './sign-in.module.css'

/**
 * Sign-in card. Form contract is unchanged (POST /api/v1/auth/session,
 * email + password only). States: empty-field validation, 401 invalid, 429
 * rate-limited, and a generic fallback for other errors — all generic (a 401
 * never distinguishes unknown email from wrong password).
 */
export function SignInForm() {
  const { t } = useTranslation()
  const { submit, isPending, isInvalidCredentials, isRateLimited, hasError } = useSignIn()
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<SignInCredentials>({ defaultValues: { email: '', password: '' } })

  const hasEmptyError = errors.email !== undefined || errors.password !== undefined
  const showInvalid = !hasEmptyError && isInvalidCredentials
  const showRateLimit = !hasEmptyError && isRateLimited
  const showGeneric = !hasEmptyError && hasError && !isInvalidCredentials && !isRateLimited
  const fieldInvalid = hasEmptyError || showInvalid

  return (
    <div className={styles['card']}>
      <div className={styles['eyebrow']}>
        <span className={styles['eyebrowDot']} />
        <span className={styles['eyebrowText']}>{t('suite.auth.hero.eyebrow')}</span>
      </div>
      <h2 className={styles['title']}>{t('suite.auth.signIn')}</h2>
      <p className={styles['subtitle']}>{t('suite.auth.subtitle')}</p>

      <form
        className={styles['form']}
        onSubmit={(event) => void handleSubmit(submit)(event)}
        noValidate
      >
        <label className={styles['field']}>
          {t('suite.auth.emailLabel')}
          <input
            type="email"
            autoComplete="username"
            className={[styles['input'], styles['inputMono']].join(' ')}
            aria-invalid={fieldInvalid}
            placeholder={t('suite.auth.emailPlaceholder')}
            {...register('email', { required: true })}
          />
        </label>

        <label className={styles['field']}>
          {t('suite.auth.passwordLabel')}
          <input
            type="password"
            autoComplete="current-password"
            className={styles['input']}
            aria-invalid={fieldInvalid}
            placeholder={t('suite.auth.passwordPlaceholder')}
            {...register('password', { required: true })}
          />
        </label>

        {hasEmptyError ? (
          <p role="alert" className={styles['empty']}>
            <Icon name="info" size={18} color="var(--color-warn)" />
            {t('suite.auth.emptyFields')}
          </p>
        ) : null}

        {showInvalid ? (
          <div role="alert" className={styles['errorBox']}>
            <Icon name="error" size={19} color="var(--color-danger)" />
            <span className={styles['errorText']}>{t('suite.auth.invalidCredentials')}</span>
          </div>
        ) : null}

        {showRateLimit ? (
          <div role="status" className={styles['statusBox']}>
            <Icon name="schedule" size={19} color="var(--color-warn)" />
            <span className={styles['statusText']}>{t('common.error.rateLimit')}</span>
          </div>
        ) : null}

        {showGeneric ? (
          <div role="alert" className={styles['errorBox']}>
            <Icon name="error" size={19} color="var(--color-danger)" />
            <span className={styles['errorText']}>{t('common.error.serverError')}</span>
          </div>
        ) : null}

        <button type="submit" className={styles['submit']} disabled={isPending}>
          {isPending ? (
            <>
              <span className={styles['spinner']} />
              {t('suite.auth.signingIn')}
            </>
          ) : (
            t('suite.auth.signIn')
          )}
        </button>
      </form>

      <p className={styles['help']}>
        <Icon name="help" size={16} color="var(--color-text-faint)" />
        {t('suite.auth.help.contact')}
      </p>
      <p className={styles['disclaimer']}>{t('suite.disclaimer.footer')}</p>
    </div>
  )
}

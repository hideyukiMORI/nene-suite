import { useForm } from 'react-hook-form'
import { useTranslation } from '@/shared/i18n'
import { useSignIn, type SignInCredentials } from '../hooks/use-sign-in'

export function SignInForm() {
  const { t } = useTranslation()
  const { submit, isPending, isInvalidCredentials } = useSignIn()
  const {
    register,
    handleSubmit,
    formState: { isSubmitted },
  } = useForm<SignInCredentials>({ defaultValues: { email: '', password: '' } })

  return (
    <form onSubmit={(event) => void handleSubmit(submit)(event)} noValidate>
      <p>{t('suite.auth.subtitle')}</p>

      <label>
        {t('suite.auth.emailLabel')}
        <input
          type="email"
          autoComplete="username"
          placeholder={t('suite.auth.emailPlaceholder')}
          {...register('email', { required: true })}
        />
      </label>

      <label>
        {t('suite.auth.passwordLabel')}
        <input
          type="password"
          autoComplete="current-password"
          placeholder={t('suite.auth.passwordPlaceholder')}
          {...register('password', { required: true })}
        />
      </label>

      {isSubmitted && isInvalidCredentials ? (
        <p role="alert">{t('suite.auth.invalidCredentials')}</p>
      ) : null}

      <button type="submit" disabled={isPending}>
        {isPending ? t('suite.auth.signingIn') : t('suite.auth.signIn')}
      </button>
    </form>
  )
}

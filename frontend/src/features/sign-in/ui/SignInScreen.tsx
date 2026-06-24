import { env } from '@/shared/config/env'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import { LoginControls } from './LoginControls'
import { SignInForm } from './SignInForm'
import { SignInHero } from './SignInHero'
import styles from './sign-in.module.css'

/** Full /login screen: split hero + sign-in card + footer + top-right controls. */
export function SignInScreen() {
  const { t } = useTranslation()
  const year = new Date().getFullYear()

  return (
    <main className={styles['screen']}>
      <LoginControls />
      <SignInHero />
      <section className={styles['formPanel']}>
        <div className={styles['formCenter']}>
          <SignInForm />
        </div>
        <footer className={styles['footer']}>
          <span className={styles['poweredBy']}>{t('suite.auth.footer.poweredBy', { year })}</span>
          {/* Website / Help links render only when their URL is configured (env). */}
          {env.siteUrl !== '' || env.helpUrl !== '' ? (
            <span className={styles['footerLinks']}>
              {env.siteUrl !== '' ? (
                <a
                  className={styles['footerLink']}
                  href={env.siteUrl}
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  <Icon name="language" size={16} />
                  {t('suite.auth.footer.site')}
                </a>
              ) : null}
              {env.helpUrl !== '' ? (
                <a
                  className={styles['footerLink']}
                  href={env.helpUrl}
                  target="_blank"
                  rel="noreferrer noopener"
                >
                  <Icon name="help" size={16} />
                  {t('suite.auth.footer.help')}
                </a>
              ) : null}
            </span>
          ) : null}
        </footer>
      </section>
    </main>
  )
}

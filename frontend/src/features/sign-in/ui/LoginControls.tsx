import { useTranslation } from '@/shared/i18n'
import { Icon, useTheme } from '@/shared/ui'
import styles from './sign-in.module.css'

/** Locale (JA|EN) + theme toggle, reachable while logged out (top-right). */
export function LoginControls() {
  const { locale, setLocale, t } = useTranslation()
  const { theme, toggleTheme } = useTheme()

  return (
    <div className={styles['controls']}>
      <div className={styles['localeGroup']} role="group" aria-label={t('suite.locale.label')}>
        <button
          type="button"
          className={styles['localeBtn']}
          data-active={locale === 'ja'}
          aria-pressed={locale === 'ja'}
          onClick={() => {
            setLocale('ja')
          }}
        >
          JA
        </button>
        <button
          type="button"
          className={styles['localeBtn']}
          data-active={locale === 'en'}
          aria-pressed={locale === 'en'}
          onClick={() => {
            setLocale('en')
          }}
        >
          EN
        </button>
      </div>
      <button
        type="button"
        className={styles['themeBtn']}
        aria-label={t('suite.theme.toggle')}
        title={t('suite.theme.toggle')}
        onClick={toggleTheme}
      >
        <Icon name={theme === 'dark' ? 'light_mode' : 'dark_mode'} size={20} />
      </button>
    </div>
  )
}

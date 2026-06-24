import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './sign-in.module.css'

const TRUST: readonly { icon: string; key: MessageKey }[] = [
  { icon: 'download', key: 'suite.auth.hero.trust.export' },
  { icon: 'dns', key: 'suite.auth.hero.trust.selfhost' },
  { icon: 'lock_open', key: 'suite.auth.hero.trust.nolockin' },
]

/** Portfolio tiles — NeNe product initials (static brand content). */
const APPS: readonly { initial: string; name: string }[] = [
  { initial: 'I', name: 'Invoice' },
  { initial: 'C', name: 'Clear' },
  { initial: 'R', name: 'Records' },
  { initial: 'V', name: 'Vault' },
  { initial: 'P', name: 'Profile' },
  { initial: 'D', name: 'Deal' },
]

/** Left brand panel — always dark (apex shell rail signature), tagline leads. */
export function SignInHero() {
  const { t } = useTranslation()

  return (
    <section className={styles['hero']}>
      <div className={styles['heroDots']} aria-hidden />
      <div className={styles['heroGlow']} aria-hidden />
      <div className={styles['heroVignette']} aria-hidden />
      <svg
        className={styles['heroWatermark']}
        viewBox="0 0 120 120"
        aria-hidden
        xmlns="http://www.w3.org/2000/svg"
      >
        <g fill="#ffffff">
          <rect x="47" y="17" width="26" height="26" rx="7" />
          <rect x="17" y="47" width="26" height="26" rx="7" />
          <rect x="77" y="47" width="26" height="26" rx="7" />
          <rect x="47" y="77" width="26" height="26" rx="7" />
          <rect x="47" y="47" width="26" height="26" rx="7" />
        </g>
      </svg>

      <div className={styles['heroInner']}>
        <div className={styles['lockup']}>
          <svg
            viewBox="0 0 120 120"
            width="38"
            height="38"
            style={{ display: 'block', flex: 'none' }}
            role="img"
            aria-label={t('suite.nav.appTitle')}
            xmlns="http://www.w3.org/2000/svg"
          >
            <rect width="120" height="120" rx="27" fill="var(--brand-deep)" />
            <rect
              x="1"
              y="1"
              width="118"
              height="118"
              rx="26"
              fill="none"
              stroke="oklch(100% 0 0 / 0.16)"
              strokeWidth="2"
            />
            <g fill="#ffffff" opacity="0.92">
              <rect x="47" y="17" width="26" height="26" rx="7" />
              <rect x="17" y="47" width="26" height="26" rx="7" />
              <rect x="77" y="47" width="26" height="26" rx="7" />
              <rect x="47" y="77" width="26" height="26" rx="7" />
            </g>
            <rect x="47" y="47" width="26" height="26" rx="7" fill="var(--side-brand)" />
          </svg>
          <span className={styles['brandName']}>NeNe Suite</span>
          <span className={styles['orchTag']}>Orchestrator</span>
        </div>

        <h1 className={`serif ${styles['headline'] ?? ''}`}>{t('suite.auth.hero.headline')}</h1>
        <p className={styles['support']}>{t('suite.auth.hero.support')}</p>

        <div className={styles['trust']}>
          {TRUST.map((chip) => (
            <span key={chip.key} className={styles['trustChip']}>
              <Icon name={chip.icon} size={17} color="var(--side-brand)" />
              {t(chip.key)}
            </span>
          ))}
        </div>

        <div className={styles['portfolio']}>
          <div className={styles['portfolioLabel']}>{t('suite.auth.hero.portfolioLabel')}</div>
          <div className={styles['tiles']}>
            {APPS.map((app) => (
              <span key={app.initial} className={styles['tile']} title={`NeNe ${app.name}`}>
                {app.initial}
              </span>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}

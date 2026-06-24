import type { InstalledApp, SsotRole } from '@/entities/installed-app'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { AppLogo, catalogIdToLogoSlug, Icon } from '@/shared/ui'
import styles from './dashboard.module.css'

const SSOT_LABEL_KEYS: Record<SsotRole, MessageKey | null> = {
  billing: 'suite.launcher.ssotBilling',
  reconciliation_evidence: 'suite.launcher.ssotEvidence',
  cms: 'suite.launcher.ssotCms',
  archive: 'suite.launcher.ssotArchive',
  none: null,
}

/** Installed-app pillar cards (the launcher grid). Real data only. */
export function Pillars({ apps }: { apps: InstalledApp[] }) {
  const { t } = useTranslation()

  return (
    <div className={styles['pillars']}>
      {apps.map((app) => {
        const slug = catalogIdToLogoSlug(app.catalogId)
        const ssotKey = SSOT_LABEL_KEYS[app.ssotRole]
        return (
          <div key={app.catalogId} className={styles['pillar']}>
            <div className={styles['pillarHead']}>
              {slug !== null ? (
                <AppLogo slug={slug} size={56} alt="" />
              ) : (
                <span className={styles['pillarFallback']}>
                  <Icon name="apps" size={28} />
                </span>
              )}
              <span className={styles['pillarBadge']}>{t('suite.home.status.active')}</span>
            </div>
            <h3 className={styles['pillarName']}>{app.name}</h3>
            <p className={styles['pillarMeta']}>{ssotKey !== null ? t(ssotKey) : null}</p>
            <div className={styles['pillarActions']}>
              <a
                className={styles['openBtn']}
                href={app.publicUrl}
                target="_blank"
                rel="noreferrer noopener"
              >
                {t('suite.home.open')}
              </a>
            </div>
          </div>
        )
      })}
    </div>
  )
}

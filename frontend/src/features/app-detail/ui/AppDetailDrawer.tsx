import { Link } from 'react-router-dom'
import { CARD_STATE_META } from '@/entities/catalog-app'
import type { SsotRole } from '@/entities/installed-app'
import { useOriginUpdates } from '@/entities/origin-update'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { AppLogo, catalogIdToLogoSlug, Drawer, Icon } from '@/shared/ui'
import { useAppDetail } from '../hooks/use-app-detail'
import styles from './app-detail.module.css'

const SSOT_LABEL_KEYS: Record<SsotRole, MessageKey | null> = {
  billing: 'suite.launcher.ssotBilling',
  reconciliation_evidence: 'suite.launcher.ssotEvidence',
  cms: 'suite.launcher.ssotCms',
  archive: 'suite.launcher.ssotArchive',
  none: null,
}

/** App detail drawer. Real catalog/installed data; change history is Origin (Phase B). */
export function AppDetailDrawer({ appId, onClose }: { appId: string; onClose: () => void }) {
  const { t } = useTranslation()
  const detail = useAppDetail(appId)
  const updates = useOriginUpdates()
  const ariaLabel = detail?.app.name ?? t('suite.appDetail.view')

  return (
    <Drawer onClose={onClose} ariaLabel={ariaLabel} closeLabel={t('common.actions.close')}>
      {detail === null ? (
        <p className={styles['loading']}>{t('common.state.loading')}</p>
      ) : (
        (() => {
          const slug = catalogIdToLogoSlug(detail.app.id)
          const meta = CARD_STATE_META[detail.state]
          const ssotKey =
            detail.installed !== null ? SSOT_LABEL_KEYS[detail.installed.ssotRole] : null
          const update =
            updates.data?.available === true
              ? updates.data.updates.find((signal) => signal.product === detail.app.id)
              : undefined
          return (
            <>
              <div className={styles['head']}>
                {slug !== null ? (
                  <AppLogo slug={slug} size={64} alt="" />
                ) : (
                  <span className={styles['logoFallback']}>
                    <Icon name="apps" size={32} />
                  </span>
                )}
                <span className={styles['badge']} style={{ color: meta.tone }}>
                  {t(meta.labelKey)}
                </span>
              </div>

              <h2 className={styles['name']}>{detail.app.name}</h2>

              <div className={styles['meta']}>
                {ssotKey !== null ? (
                  <p className={styles['metaLine']}>
                    {t('suite.appDetail.role')}: {t(ssotKey)}
                  </p>
                ) : null}
                {detail.app.requires.length > 0 ? (
                  <p className={styles['metaLine']}>
                    {t('suite.catalog.requires', { names: detail.app.requires.join(', ') })}
                  </p>
                ) : null}
                {detail.app.provides.length > 0 ? (
                  <p className={styles['metaLine']}>
                    {t('suite.appDetail.provides', { names: detail.app.provides.join(', ') })}
                  </p>
                ) : null}
              </div>

              <div className={styles['section']}>
                <h3 className={styles['sectionTitle']}>
                  {t('suite.appDetail.changelog')}
                  <span className={styles['originTag']}>Origin</span>
                </h3>
                {update !== undefined && update.latestVersion !== null ? (
                  <div className={styles['changelog']}>
                    <span className={styles['changelogVersion']}>
                      {t('suite.appDetail.latest', { version: update.latestVersion })}
                    </span>
                    {update.changelogUrl !== null ? (
                      <a
                        className={styles['changelogLink']}
                        href={update.changelogUrl}
                        target="_blank"
                        rel="noreferrer noopener"
                      >
                        {t('suite.appDetail.viewChangelog')}
                        <Icon name="open_in_new" size={15} />
                      </a>
                    ) : null}
                  </div>
                ) : (
                  <p className={styles['placeholder']}>
                    {t('suite.appDetail.changelogPlaceholder')}
                  </p>
                )}
              </div>

              <div className={styles['footer']}>
                {detail.state === 'installed' && detail.installed !== null ? (
                  <a
                    className={styles['openBtn']}
                    href={detail.installed.publicUrl}
                    target="_blank"
                    rel="noreferrer noopener"
                  >
                    {t('suite.home.open')}
                  </a>
                ) : null}
                {detail.state === 'installable' ? (
                  <Link className={styles['getBtn']} to="/install" onClick={onClose}>
                    {t('suite.catalog.get')}
                  </Link>
                ) : null}
              </div>
            </>
          )
        })()
      )}
    </Drawer>
  )
}

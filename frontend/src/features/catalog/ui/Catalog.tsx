import { useState } from 'react'
import { Link } from 'react-router-dom'
import {
  CARD_STATE_META,
  CARD_STATE_ORDER,
  deriveCardState,
  type CatalogCardState,
} from '@/entities/catalog-app'
import { AppDetailDrawer } from '@/features/app-detail'
import { useTranslation } from '@/shared/i18n'
import { AppLogo, catalogIdToLogoSlug, Icon } from '@/shared/ui'
import { useCatalog } from '../hooks/use-catalog'
import styles from './catalog.module.css'

type Filter = 'all' | CatalogCardState
const SKELETON_KEYS = ['s1', 's2', 's3', 's4', 's5', 's6']

export function Catalog() {
  const { t } = useTranslation()
  const { apps, installedIds, installedUrlById, isLoading, isError, refetch } = useCatalog()
  const [filter, setFilter] = useState<Filter>('all')
  const [detailId, setDetailId] = useState<string | null>(null)

  if (isLoading) {
    return (
      <div className={styles['root']}>
        <h1 className={styles['title']}>{t('suite.catalog.title')}</h1>
        <p className={styles['subtitle']}>{t('suite.catalog.subtitle')}</p>
        <div className={styles['grid']}>
          {SKELETON_KEYS.map((key) => (
            <div key={key} className={styles['skeletonCard']} />
          ))}
        </div>
      </div>
    )
  }

  if (isError) {
    return (
      <div className={styles['root']}>
        <h1 className={styles['title']}>{t('suite.catalog.title')}</h1>
        <div className={styles['errorPanel']} role="alert">
          <Icon name="error" size={40} fill color="var(--color-danger)" />
          <p>{t('common.state.error')}</p>
          <button type="button" className={styles['retryBtn']} onClick={refetch}>
            <Icon name="refresh" size={18} />
            {t('common.actions.retry')}
          </button>
        </div>
      </div>
    )
  }

  const nameById = new Map(apps.map((app) => [app.id, app.name]))
  const decorated = apps.map((app) => ({ app, state: deriveCardState(app, installedIds) }))
  const presentStates = CARD_STATE_ORDER.filter((state) =>
    decorated.some((entry) => entry.state === state),
  )
  const chips: Filter[] = ['all', ...presentStates]
  const visible = filter === 'all' ? decorated : decorated.filter((entry) => entry.state === filter)

  return (
    <div className={styles['root']}>
      <h1 className={styles['title']}>{t('suite.catalog.title')}</h1>
      <p className={styles['subtitle']}>{t('suite.catalog.subtitle')}</p>

      <div className={styles['chips']}>
        {chips.map((value) => (
          <button
            key={value}
            type="button"
            className={styles['chip']}
            data-active={filter === value}
            onClick={() => {
              setFilter(value)
            }}
          >
            {value === 'all' ? t('suite.catalog.filter.all') : t(CARD_STATE_META[value].labelKey)}
          </button>
        ))}
      </div>

      {visible.length === 0 ? (
        <div className={styles['empty']}>
          <Icon name="apps" size={40} color="var(--color-text-faint)" />
          <p>{t('suite.catalog.empty')}</p>
        </div>
      ) : (
        <div className={styles['grid']}>
          {visible.map(({ app, state }) => {
            const slug = catalogIdToLogoSlug(app.id)
            const meta = CARD_STATE_META[state]
            const url = installedUrlById.get(app.id)
            const requiresNames = app.requires.map((id) => nameById.get(id) ?? id).join(', ')
            return (
              <div key={app.id} className={styles['card']}>
                <div className={styles['cardHead']}>
                  {slug !== null ? (
                    <AppLogo slug={slug} size={48} alt="" />
                  ) : (
                    <span className={styles['logoFallback']}>
                      <Icon name="apps" size={24} />
                    </span>
                  )}
                  <span className={styles['badge']} style={{ color: meta.tone }}>
                    {t(meta.labelKey)}
                  </span>
                </div>
                <h3 className={styles['name']}>{app.name}</h3>
                <p className={styles['meta']}>
                  {app.requires.length > 0
                    ? t('suite.catalog.requires', { names: requiresNames })
                    : null}
                </p>
                {app.installedVersion !== null || app.availableVersion !== null ? (
                  <p className={styles['meta']}>
                    {[
                      app.installedVersion !== null
                        ? t('suite.catalog.version.installed', { version: app.installedVersion })
                        : null,
                      app.availableVersion !== null
                        ? t('suite.catalog.version.available', { version: app.availableVersion })
                        : null,
                    ]
                      .filter((part) => part !== null)
                      .join(' · ')}
                  </p>
                ) : null}
                <div className={styles['actions']}>
                  <button
                    type="button"
                    className={styles['detailBtn']}
                    onClick={() => {
                      setDetailId(app.id)
                    }}
                  >
                    {t('suite.appDetail.view')}
                  </button>
                  {state === 'installed' && url !== undefined ? (
                    <a
                      className={styles['openBtn']}
                      href={url}
                      target="_blank"
                      rel="noreferrer noopener"
                    >
                      {t('suite.home.open')}
                    </a>
                  ) : null}
                  {state === 'installable' ? (
                    <Link className={styles['getBtn']} to="/install">
                      {t('suite.catalog.get')}
                    </Link>
                  ) : null}
                </div>
              </div>
            )
          })}
        </div>
      )}

      {detailId !== null ? (
        <AppDetailDrawer
          appId={detailId}
          onClose={() => {
            setDetailId(null)
          }}
        />
      ) : null}
    </div>
  )
}

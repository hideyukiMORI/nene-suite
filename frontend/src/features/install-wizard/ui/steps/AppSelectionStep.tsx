import { useState } from 'react'
import type { CatalogApp } from '@/entities/catalog-app'
import { useTranslation } from '@/shared/i18n'
import styles from '../install-wizard.module.css'

interface AppSelectionStepProps {
  apps: CatalogApp[]
  isPending: boolean
  onSubmit: (ids: string[]) => void
  /** Prefill when navigating back to this step — the session already has a selection. */
  initialSelected?: string[]
}

export function AppSelectionStep({
  apps,
  isPending,
  onSubmit,
  initialSelected,
}: AppSelectionStepProps) {
  const { t } = useTranslation()
  const installable = apps.filter((app) => app.status === 'installable')
  const [selected, setSelected] = useState<string[]>(initialSelected ?? [])

  const toggle = (id: string): void => {
    setSelected((prev) =>
      prev.includes(id) ? prev.filter((value) => value !== id) : [...prev, id],
    )
  }

  return (
    <form
      onSubmit={(event) => {
        event.preventDefault()
        if (!isPending && selected.length > 0) onSubmit(selected)
      }}
      noValidate
    >
      <h3 className={styles['stepTitle']}>{t('suite.install.apps.title')}</h3>
      <p className={styles['stepDesc']}>{t('suite.install.apps.description')}</p>

      {installable.length === 0 ? (
        <p className={styles['stepDesc']}>{t('suite.install.apps.empty')}</p>
      ) : (
        <div className={styles['appList']}>
          {installable.map((app) => (
            <label key={app.id} className={styles['appRow']}>
              <input
                type="checkbox"
                checked={selected.includes(app.id)}
                onChange={() => {
                  toggle(app.id)
                }}
              />
              <span className={styles['appName']}>{app.name}</span>
              {app.requires.length > 0 ? (
                <span className={styles['requiresHint']}>
                  {t('suite.install.apps.requires', {
                    appName: app.requires
                      .map((id) => apps.find((candidate) => candidate.id === id)?.name ?? id)
                      .join('、'),
                  })}
                </span>
              ) : null}
            </label>
          ))}
        </div>
      )}

      <p className={styles['count']}>
        {t('suite.install.apps.selectedCount', { count: selected.length })}
      </p>
      <div className={styles['actions']}>
        <button
          type="submit"
          className={styles['primaryBtn']}
          disabled={isPending || selected.length === 0}
        >
          {t('common.actions.next')}
        </button>
      </div>
    </form>
  )
}

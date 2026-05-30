import { useState } from 'react'
import type { CatalogApp } from '@/entities/catalog-app'
import { useTranslation } from '@/shared/i18n'

interface AppSelectionStepProps {
  apps: CatalogApp[]
  isPending: boolean
  onSubmit: (ids: string[]) => void
}

export function AppSelectionStep({ apps, isPending, onSubmit }: AppSelectionStepProps) {
  const { t } = useTranslation()
  const installable = apps.filter((app) => app.status === 'installable')
  const [selected, setSelected] = useState<string[]>([])

  const toggle = (id: string): void => {
    setSelected((prev) =>
      prev.includes(id) ? prev.filter((value) => value !== id) : [...prev, id],
    )
  }

  return (
    <section>
      <h3>{t('suite.install.apps.title')}</h3>
      <p>{t('suite.install.apps.description')}</p>

      {installable.length === 0 ? (
        <p>{t('suite.install.apps.empty')}</p>
      ) : (
        <ul>
          {installable.map((app) => (
            <li key={app.id}>
              <label>
                <input
                  type="checkbox"
                  checked={selected.includes(app.id)}
                  onChange={() => {
                    toggle(app.id)
                  }}
                />
                {app.name}
              </label>
              {app.requires.length > 0 ? (
                <small>
                  {t('suite.install.apps.requiredBy', { appName: app.requires.join(', ') })}
                </small>
              ) : null}
            </li>
          ))}
        </ul>
      )}

      <p>{t('suite.install.apps.selectedCount', { count: selected.length })}</p>
      <button
        type="button"
        disabled={isPending || selected.length === 0}
        onClick={() => {
          onSubmit(selected)
        }}
      >
        {t('common.actions.next')}
      </button>
    </section>
  )
}

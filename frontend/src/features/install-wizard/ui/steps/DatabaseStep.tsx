import { useState } from 'react'
import type { DatabaseTargetInput } from '@/entities/install-session'
import { useTranslation } from '@/shared/i18n'
import styles from '../install-wizard.module.css'

interface DatabaseStepApp {
  id: string
  name: string
}

interface DatabaseStepProps {
  apps: DatabaseStepApp[]
  isPending: boolean
  onSubmit: (targets: DatabaseTargetInput[]) => void
}

type Mode = DatabaseTargetInput['mode']

interface Draft {
  mode: Mode
  server: string
  name: string
}

const DEFAULT_DRAFT: Draft = { mode: 'provision', server: '', name: '' }

function draftOf(drafts: Record<string, Draft>, id: string): Draft {
  return drafts[id] ?? DEFAULT_DRAFT
}

/**
 * Per-app database target step (ADR 0022 mode A): the operator picks `provision`
 * (the suite creates a new database) or `adopt` (register an existing one), and for
 * adopt may supply a non-secret server label and the existing database name. Both
 * adopt fields are optional — empty means the suite server / naming convention.
 */
export function DatabaseStep({ apps, isPending, onSubmit }: DatabaseStepProps) {
  const { t } = useTranslation()
  const [drafts, setDrafts] = useState<Record<string, Draft>>({})

  const update = (id: string, patch: Partial<Draft>): void => {
    setDrafts((prev) => ({ ...prev, [id]: { ...draftOf(prev, id), ...patch } }))
  }

  const submit = (): void => {
    const targets = apps.map((app): DatabaseTargetInput => {
      const draft = draftOf(drafts, app.id)
      if (draft.mode !== 'adopt') {
        return { catalogId: app.id, mode: 'provision' }
      }
      const server = draft.server.trim()
      const name = draft.name.trim()
      return {
        catalogId: app.id,
        mode: 'adopt',
        ...(server !== '' ? { server } : {}),
        ...(name !== '' ? { name } : {}),
      }
    })
    onSubmit(targets)
  }

  return (
    <div>
      <h3 className={styles['stepTitle']}>{t('suite.install.database.title')}</h3>
      <p className={styles['stepDesc']}>{t('suite.install.database.description')}</p>

      {apps.length === 0 ? (
        <p className={styles['stepDesc']}>{t('suite.install.database.empty')}</p>
      ) : (
        <div className={styles['appList']}>
          {apps.map((app) => {
            const draft = draftOf(drafts, app.id)
            return (
              <div key={app.id} className={styles['dbAppCard']}>
                <div className={styles['dbAppHead']}>
                  <span className={styles['appName']}>{app.name}</span>
                  <select
                    className={styles['dbSelect']}
                    aria-label={t('suite.install.database.mode.label', { appName: app.name })}
                    value={draft.mode}
                    onChange={(event) => {
                      update(app.id, { mode: event.target.value as Mode })
                    }}
                  >
                    <option value="provision">{t('suite.install.database.mode.provision')}</option>
                    <option value="adopt">{t('suite.install.database.mode.adopt')}</option>
                  </select>
                </div>

                {draft.mode === 'adopt' ? (
                  <div className={styles['dbAdoptFields']}>
                    <label className={styles['dbField']}>
                      <span className={styles['dbLabel']}>
                        {t('suite.install.database.server.label')}
                      </span>
                      <input
                        className={styles['dbInput']}
                        value={draft.server}
                        placeholder={t('suite.install.database.server.placeholder')}
                        onChange={(event) => {
                          update(app.id, { server: event.target.value })
                        }}
                      />
                    </label>
                    <label className={styles['dbField']}>
                      <span className={styles['dbLabel']}>
                        {t('suite.install.database.name.label')}
                      </span>
                      <input
                        className={styles['dbInput']}
                        value={draft.name}
                        placeholder={t('suite.install.database.name.placeholder')}
                        onChange={(event) => {
                          update(app.id, { name: event.target.value })
                        }}
                      />
                    </label>
                  </div>
                ) : null}
              </div>
            )
          })}
        </div>
      )}

      <div className={styles['actions']}>
        <button
          type="button"
          className={styles['primaryBtn']}
          disabled={isPending}
          onClick={submit}
        >
          {t('common.actions.next')}
        </button>
      </div>
    </div>
  )
}

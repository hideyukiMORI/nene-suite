import { useState } from 'react'
import type { DatabaseTargetInput } from '@/entities/install-session'
import { useTranslation } from '@/shared/i18n'
import { Icon, InfoHint } from '@/shared/ui'
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
 * Per-app database target step (ADR 0022 mode A). Presentation polish only — the
 * `DatabaseTargetInput` payload, the draft state, the submit mapping, and the
 * `suite.install.database.*` keys are unchanged. The plain `<select>` becomes a
 * segmented Provision/Adopt toggle; the adopt sub-form is a revealed tinted panel;
 * each app shows a one-line summary.
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

  const initialOf = (name: string): string =>
    name
      .trim()
      .split(/\s+/)
      .map((w) => w[0])
      .slice(0, 2)
      .join('')
      .toUpperCase()

  const slugOf = (name: string): string => name.toLowerCase().replace(/\s+/g, '-')

  return (
    <div>
      <h3 className={styles['stepTitle']}>
        {t('suite.install.database.title')}{' '}
        <InfoHint
          text="provision（新規作成）は Suite が新しいデータベースを作る方式です。adopt（既存採用）は、すでにあるデータベースをそのまま登録して使い、Suite は中身を作りも変えもしません。迷ったら provision のままで大丈夫です。"
          label="データベースの割り当てとは"
        />
      </h3>
      <p className={styles['stepDesc']}>{t('suite.install.database.description')}</p>

      {apps.length === 0 ? (
        <p className={styles['stepDesc']}>{t('suite.install.database.empty')}</p>
      ) : (
        <div className={styles['appList']}>
          {apps.map((app) => {
            const draft = draftOf(drafts, app.id)
            const isAdopt = draft.mode === 'adopt'
            const slug = slugOf(app.name)
            const summary = isAdopt
              ? `${slug} → ${t('suite.install.database.summary.adopt')}`
              : `${slug} → ${t('suite.install.database.summary.provision')}`

            return (
              <div key={app.id} className={styles['dbAppCard']}>
                <div className={styles['dbAppHead']}>
                  <span className={styles['dbAppIdentity']}>
                    <span className={styles['dbAppMark']}>{initialOf(app.name)}</span>
                    <span>
                      <span className={styles['dbAppName']}>{app.name}</span>
                      <span className={styles['dbAppSlug']}>{slug}</span>
                    </span>
                  </span>

                  <div
                    className={styles['dbToggle']}
                    role="radiogroup"
                    aria-label={t('suite.install.database.mode.label', { appName: app.name })}
                  >
                    <button
                      type="button"
                      role="radio"
                      aria-checked={!isAdopt}
                      data-active={!isAdopt}
                      className={styles['dbToggleBtn']}
                      onClick={() => {
                        update(app.id, { mode: 'provision' })
                      }}
                    >
                      <Icon name="add_circle" size={16} />
                      {t('suite.install.database.mode.provision')}
                    </button>
                    <button
                      type="button"
                      role="radio"
                      aria-checked={isAdopt}
                      data-active={isAdopt}
                      className={styles['dbToggleBtn']}
                      onClick={() => {
                        update(app.id, { mode: 'adopt' })
                      }}
                    >
                      <Icon name="link" size={16} />
                      {t('suite.install.database.mode.adopt')}
                    </button>
                  </div>
                </div>

                {isAdopt ? (
                  <div className={styles['dbAdoptPanel']}>
                    <p className={styles['dbAdoptNote']}>
                      <Icon name="info" size={16} className={styles['dbAdoptNoteIcon']} />
                      {t('suite.install.database.adopt.note')}
                    </p>
                    <div className={styles['dbAdoptGrid']}>
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
                        <span className={styles['dbFieldHelp']}>
                          {t('suite.install.database.server.help')}
                        </span>
                      </label>
                      <label className={styles['dbField']}>
                        <span className={styles['dbLabel']}>
                          {t('suite.install.database.name.label')}
                        </span>
                        <input
                          className={styles['dbInputMono']}
                          value={draft.name}
                          placeholder={t('suite.install.database.name.placeholder')}
                          onChange={(event) => {
                            update(app.id, { name: event.target.value })
                          }}
                        />
                        <span className={styles['dbFieldHelp']}>
                          {t('suite.install.database.name.help')}
                        </span>
                      </label>
                    </div>
                  </div>
                ) : null}

                <p className={styles['dbSummary']}>
                  <Icon name="bolt" size={15} className={styles['dbSummaryIcon']} />
                  {summary}
                </p>
              </div>
            )
          })}
        </div>
      )}

      <div className={styles['actions']}>
        <button
          type="button"
          className={styles['primaryBtn']}
          disabled={isPending || apps.length === 0}
          onClick={submit}
        >
          {t('common.actions.next')}
          <Icon name="arrow_forward" size={18} />
        </button>
      </div>
    </div>
  )
}

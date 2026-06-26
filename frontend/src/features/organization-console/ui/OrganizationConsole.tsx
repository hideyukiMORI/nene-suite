import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useForm } from 'react-hook-form'
import type { Organization, RenameOrganizationInput } from '@/entities/organization'
import { useTranslation } from '@/shared/i18n'
import { ErrorState, Icon, LoadingState, PlaceholderState } from '@/shared/ui'
import {
  useOrganizationConsole,
  type CreateOrganizationFields,
} from '../hooks/use-organization-console'
import styles from './organization-console.module.css'

/**
 * Superadmin organization console (Admin → Organizations tab).
 * Presentation redesign only — data flow, RHF fields (`name`, `slug`, row `name`),
 * mutations, and `suite.org.*` i18n keys are unchanged.
 */
export function OrganizationConsole() {
  const { t } = useTranslation()
  const {
    organizations,
    isLoading,
    isError,
    createOrganization,
    renameOrganization,
    disableOrganization,
    isCreating,
    isRenaming,
    isDisabling,
    createErrorKey,
  } = useOrganizationConsole()

  const { register, handleSubmit, reset } = useForm<CreateOrganizationFields>({
    defaultValues: { name: '', slug: '' },
  })

  // presentation-only UI state
  const [menuOpenId, setMenuOpenId] = useState<string | null>(null)
  const [editingId, setEditingId] = useState<string | null>(null)

  const submitCreate = (fields: CreateOrganizationFields): void => {
    createOrganization(fields)
    reset()
  }

  if (isLoading) return <LoadingState label={t('common.state.loading')} />
  if (isError) return <ErrorState label={t('common.state.error')} />

  return (
    <div className={styles['stack']}>
      {/* Create organization */}
      <section className={styles['createCard']}>
        <div className={styles['cardHead']}>
          <Icon name="add_business" size={19} className={styles['cardIcon']} />
          <h2 className={styles['cardTitle']}>{t('suite.org.create.title')}</h2>
        </div>
        <form
          className={styles['createForm']}
          onSubmit={(event) => void handleSubmit(submitCreate)(event)}
          noValidate
        >
          <label className={styles['field']}>
            <span className={styles['label']}>{t('suite.org.create.nameLabel')}</span>
            <input
              className={styles['input']}
              placeholder={t('suite.org.create.namePlaceholder')}
              {...register('name', { required: true })}
            />
          </label>
          <label className={styles['field']}>
            <span className={styles['label']}>{t('suite.org.create.slugLabel')}</span>
            <input
              className={styles['inputMono']}
              placeholder={t('suite.org.create.slugPlaceholder')}
              {...register('slug', { required: true })}
            />
          </label>
          <button type="submit" className={styles['primaryBtn']} disabled={isCreating}>
            <Icon name="add" size={18} />
            {isCreating ? t('suite.org.create.submitting') : t('suite.org.create.submit')}
          </button>
        </form>
        {createErrorKey !== null ? (
          <p className={styles['errorText']} role="alert">
            <Icon name="error" size={17} fill />
            {t(createErrorKey)}
          </p>
        ) : null}
      </section>

      {/* Organizations list */}
      {organizations.length === 0 ? (
        <PlaceholderState icon="corporate_fare" title={t('suite.org.empty')} />
      ) : (
        <div className={styles['table']}>
          <div className={styles['headRow']}>
            <span>{t('suite.org.column.name')}</span>
            <span>{t('suite.org.column.slug')}</span>
            <span>{t('suite.org.column.status')}</span>
            <span className={styles['headActions']}>{t('suite.org.column.actions')}</span>
          </div>
          {organizations.map((organization) => (
            <OrganizationRow
              key={organization.id}
              organization={organization}
              onRename={renameOrganization}
              onDisable={disableOrganization}
              isRenaming={isRenaming}
              isDisabling={isDisabling}
              menuOpen={menuOpenId === organization.id}
              onToggleMenu={() => {
                setMenuOpenId((id) => (id === organization.id ? null : organization.id))
              }}
              editing={editingId === organization.id}
              onStartEdit={() => {
                setEditingId(organization.id)
                setMenuOpenId(null)
              }}
              onStopEdit={() => {
                setEditingId(null)
              }}
            />
          ))}
        </div>
      )}

      {menuOpenId !== null ? (
        <button
          type="button"
          className={styles['overlay']}
          aria-label={t('common.actions.close')}
          onClick={() => {
            setMenuOpenId(null)
          }}
        />
      ) : null}
    </div>
  )
}

interface OrganizationRowProps {
  organization: Organization
  onRename: (input: RenameOrganizationInput) => void
  onDisable: (id: string) => void
  isRenaming: boolean
  isDisabling: boolean
  menuOpen: boolean
  onToggleMenu: () => void
  editing: boolean
  onStartEdit: () => void
  onStopEdit: () => void
}

function OrganizationRow({
  organization,
  onRename,
  onDisable,
  isRenaming,
  isDisabling,
  menuOpen,
  onToggleMenu,
  editing,
  onStartEdit,
  onStopEdit,
}: OrganizationRowProps) {
  const { t } = useTranslation()
  const { register, handleSubmit, reset } = useForm<{ name: string }>({
    defaultValues: { name: organization.name },
  })

  const submitRename = (fields: { name: string }): void => {
    onRename({ id: organization.id, name: fields.name })
    onStopEdit()
  }

  const isDisabled = organization.status === 'disabled'
  const initial = organization.name
    .trim()
    .split(/\s+/)
    .map((w) => w[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()

  return (
    <div className={styles['row']}>
      {/* name (display / inline-edit) */}
      {editing ? (
        <form
          className={styles['renameWrap']}
          onSubmit={(event) => void handleSubmit(submitRename)(event)}
          noValidate
        >
          <input
            className={styles['renameInput']}
            aria-label={t('suite.org.rename.nameLabel')}
            {...register('name', { required: true })}
          />
          <button type="submit" className={styles['iconBtnSave']} disabled={isRenaming}>
            <Icon name="check" size={18} />
          </button>
          <button
            type="button"
            className={styles['iconBtnCancel']}
            onClick={() => {
              reset({ name: organization.name })
              onStopEdit()
            }}
          >
            <Icon name="close" size={18} />
          </button>
        </form>
      ) : (
        <div className={styles['nameCell']}>
          <span className={styles['initial']}>{initial}</span>
          <span className={styles['orgName']}>{organization.name}</span>
        </div>
      )}

      {/* slug */}
      <span className={styles['slug']}>{organization.slug}</span>

      {/* status */}
      <span>
        <span className={isDisabled ? styles['statusDisabled'] : styles['statusActive']}>
          <span className={styles['dot']} />
          {t(`suite.org.status.${organization.status}`)}
        </span>
      </span>

      {/* actions */}
      <div className={styles['actions']}>
        <Link
          to={`/admin/organizations/${organization.id}/memberships`}
          className={styles['linkBtn']}
        >
          <Icon name="group" size={16} />
          {t('suite.org.members')}
        </Link>
        <button
          type="button"
          className={styles['iconBtn']}
          aria-haspopup="menu"
          aria-expanded={menuOpen}
          aria-label={t('suite.org.column.actions')}
          onClick={onToggleMenu}
        >
          <Icon name="more_horiz" size={18} />
        </button>
        {menuOpen ? (
          <div className={styles['menu']} role="menu">
            <button
              type="button"
              className={styles['menuItem']}
              role="menuitem"
              onClick={onStartEdit}
            >
              <Icon name="edit" size={18} className={styles['menuIcon']} />
              {t('suite.org.rename.action')}
            </button>
            {isDisabled ? (
              <span className={styles['menuItemDisabled']}>
                <Icon name="block" size={18} />
                {t('suite.org.disable.action')}
              </span>
            ) : (
              <button
                type="button"
                className={styles['menuItemDanger']}
                role="menuitem"
                disabled={isDisabling}
                onClick={() => {
                  onDisable(organization.id)
                }}
              >
                <Icon name="block" size={18} />
                {t('suite.org.disable.action')}
              </button>
            )}
          </div>
        ) : null}
      </div>
    </div>
  )
}

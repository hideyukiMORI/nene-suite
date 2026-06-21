import { useForm } from 'react-hook-form'
import type { Organization, RenameOrganizationInput } from '@/entities/organization'
import { useTranslation } from '@/shared/i18n'
import { ErrorState, LoadingState } from '@/shared/ui'
import {
  useOrganizationConsole,
  type CreateOrganizationFields,
} from '../hooks/use-organization-console'

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

  const submitCreate = (fields: CreateOrganizationFields): void => {
    createOrganization(fields)
    reset()
  }

  if (isLoading) {
    return <LoadingState label={t('common.state.loading')} />
  }

  if (isError) {
    return <ErrorState label={t('common.state.error')} />
  }

  return (
    <div>
      <form onSubmit={(event) => void handleSubmit(submitCreate)(event)} noValidate>
        <h3>{t('suite.org.create.title')}</h3>
        <label>
          {t('suite.org.create.nameLabel')}
          <input
            placeholder={t('suite.org.create.namePlaceholder')}
            {...register('name', { required: true })}
          />
        </label>
        <label>
          {t('suite.org.create.slugLabel')}
          <input
            placeholder={t('suite.org.create.slugPlaceholder')}
            {...register('slug', { required: true })}
          />
        </label>
        {createErrorKey !== null ? <p role="alert">{t(createErrorKey)}</p> : null}
        <button type="submit" disabled={isCreating}>
          {isCreating ? t('suite.org.create.submitting') : t('suite.org.create.submit')}
        </button>
      </form>

      {organizations.length === 0 ? (
        <p>{t('suite.org.empty')}</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>{t('suite.org.column.name')}</th>
              <th>{t('suite.org.column.slug')}</th>
              <th>{t('suite.org.column.status')}</th>
              <th>{t('suite.org.column.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {organizations.map((organization) => (
              <OrganizationRow
                key={organization.id}
                organization={organization}
                onRename={renameOrganization}
                onDisable={disableOrganization}
                isRenaming={isRenaming}
                isDisabling={isDisabling}
              />
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

interface OrganizationRowProps {
  organization: Organization
  onRename: (input: RenameOrganizationInput) => void
  onDisable: (id: string) => void
  isRenaming: boolean
  isDisabling: boolean
}

function OrganizationRow({
  organization,
  onRename,
  onDisable,
  isRenaming,
  isDisabling,
}: OrganizationRowProps) {
  const { t } = useTranslation()
  const { register, handleSubmit } = useForm<{ name: string }>({
    defaultValues: { name: organization.name },
  })

  const submitRename = (fields: { name: string }): void => {
    onRename({ id: organization.id, name: fields.name })
  }

  const isDisabled = organization.status === 'disabled'

  return (
    <tr>
      <td>{organization.name}</td>
      <td>{organization.slug}</td>
      <td>{t(`suite.org.status.${organization.status}`)}</td>
      <td>
        <form onSubmit={(event) => void handleSubmit(submitRename)(event)} noValidate>
          <label>
            {t('suite.org.rename.nameLabel')}
            <input {...register('name', { required: true })} />
          </label>
          <button type="submit" disabled={isRenaming}>
            {isRenaming ? t('suite.org.rename.submitting') : t('suite.org.rename.action')}
          </button>
        </form>
        <button
          type="button"
          disabled={isDisabled || isDisabling}
          onClick={() => {
            onDisable(organization.id)
          }}
        >
          {t('suite.org.disable.action')}
        </button>
      </td>
    </tr>
  )
}

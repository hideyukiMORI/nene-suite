import { useForm } from 'react-hook-form'
import type {
  ChangeMembershipRoleInput,
  MembershipRole,
  OrganizationMember,
} from '@/entities/membership'
import { useTranslation } from '@/shared/i18n'
import { ErrorState, LoadingState } from '@/shared/ui'
import { useMembershipConsole, type GrantMemberFields } from '../hooks/use-membership-console'

const ROLES: readonly MembershipRole[] = ['admin', 'member', 'viewer']

interface MembershipConsoleProps {
  organizationId: string
}

export function MembershipConsole({ organizationId }: MembershipConsoleProps) {
  const { t } = useTranslation()
  const {
    members,
    operators,
    isLoading,
    isError,
    grantMember,
    changeRole,
    revokeMember,
    isGranting,
    isChanging,
    isRevoking,
    grantErrorKey,
  } = useMembershipConsole(organizationId)

  const { register, handleSubmit, reset } = useForm<GrantMemberFields>({
    defaultValues: { operatorId: '', role: 'member' },
  })

  const submitGrant = (fields: GrantMemberFields): void => {
    grantMember(fields)
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
      <form onSubmit={(event) => void handleSubmit(submitGrant)(event)} noValidate>
        <h3>{t('suite.member.grant.title')}</h3>
        {operators.length === 0 ? (
          <p>{t('suite.member.grant.noOperators')}</p>
        ) : (
          <>
            <label>
              {t('suite.member.grant.operatorLabel')}
              <select {...register('operatorId', { required: true })}>
                <option value="" disabled>
                  {t('suite.member.grant.operatorPlaceholder')}
                </option>
                {operators.map((operator) => (
                  <option key={operator.id} value={operator.id}>
                    {operator.displayName !== null
                      ? `${operator.displayName} (${operator.email})`
                      : operator.email}
                  </option>
                ))}
              </select>
            </label>
            <label>
              {t('suite.member.grant.roleLabel')}
              <select {...register('role', { required: true })}>
                {ROLES.map((role) => (
                  <option key={role} value={role}>
                    {t(`suite.member.role.${role}`)}
                  </option>
                ))}
              </select>
            </label>
            {grantErrorKey !== null ? <p role="alert">{t(grantErrorKey)}</p> : null}
            <button type="submit" disabled={isGranting}>
              {isGranting ? t('suite.member.grant.submitting') : t('suite.member.grant.submit')}
            </button>
          </>
        )}
      </form>

      {members.length === 0 ? (
        <p>{t('suite.member.empty')}</p>
      ) : (
        <table>
          <thead>
            <tr>
              <th>{t('suite.member.column.operator')}</th>
              <th>{t('suite.member.column.role')}</th>
              <th>{t('suite.member.column.actions')}</th>
            </tr>
          </thead>
          <tbody>
            {members.map((member) => (
              <MemberRow
                key={member.membershipId}
                member={member}
                onChangeRole={changeRole}
                onRevoke={revokeMember}
                isChanging={isChanging}
                isRevoking={isRevoking}
              />
            ))}
          </tbody>
        </table>
      )}
    </div>
  )
}

interface MemberRowProps {
  member: OrganizationMember
  onChangeRole: (input: ChangeMembershipRoleInput) => void
  onRevoke: (membershipId: string) => void
  isChanging: boolean
  isRevoking: boolean
}

function MemberRow({ member, onChangeRole, onRevoke, isChanging, isRevoking }: MemberRowProps) {
  const { t } = useTranslation()

  return (
    <tr>
      <td>{member.email ?? t('suite.member.stale', { operatorId: member.operatorId })}</td>
      <td>
        <label>
          {t('suite.member.column.role')}
          <select
            value={member.role}
            disabled={isChanging}
            onChange={(event) => {
              const role = ROLES.find((candidate) => candidate === event.target.value)
              if (role !== undefined) {
                onChangeRole({ membershipId: member.membershipId, role })
              }
            }}
          >
            {ROLES.map((role) => (
              <option key={role} value={role}>
                {t(`suite.member.role.${role}`)}
              </option>
            ))}
          </select>
        </label>
      </td>
      <td>
        <button
          type="button"
          disabled={isRevoking}
          onClick={() => {
            onRevoke(member.membershipId)
          }}
        >
          {t('suite.member.revoke.action')}
        </button>
      </td>
    </tr>
  )
}

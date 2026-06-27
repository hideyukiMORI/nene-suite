import { useState } from 'react'
import { useForm } from 'react-hook-form'
import type {
  ChangeMembershipRoleInput,
  MembershipRole,
  OrganizationMember,
} from '@/entities/membership'
import { useTranslation } from '@/shared/i18n'
import { ErrorState, Icon, InfoHint, LoadingState, PlaceholderState } from '@/shared/ui'
import { useMembershipConsole, type GrantMemberFields } from '../hooks/use-membership-console'
import styles from './membership-console.module.css'

const ROLES: readonly MembershipRole[] = ['admin', 'member', 'viewer']

interface MembershipConsoleProps {
  organizationId: string
}

/**
 * Membership console (Admin → Members tab). Presentation redesign only — data flow,
 * RHF fields (`operatorId`, `role`), mutations, and `suite.member.*` keys unchanged.
 */
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
    changeErrorKey,
    revokeErrorKey,
  } = useMembershipConsole(organizationId)

  const { register, handleSubmit, reset } = useForm<GrantMemberFields>({
    defaultValues: { operatorId: '', role: 'member' },
  })

  const submitGrant = (fields: GrantMemberFields): void => {
    grantMember(fields, {
      onSuccess: () => {
        reset()
      },
    })
  }

  if (isLoading) return <LoadingState label={t('common.state.loading')} />
  if (isError) return <ErrorState label={t('common.state.error')} />

  const adminCount = members.filter((member) => member.role === 'admin').length
  const memberActionErrorKey = changeErrorKey ?? revokeErrorKey

  return (
    <div className={styles['stack']}>
      {/* Grant member */}
      <section className={styles['grantCard']}>
        <div className={styles['cardHead']}>
          <Icon name="person_add" size={19} className={styles['cardIcon']} />
          <h2 className={styles['cardTitle']}>{t('suite.member.grant.title')}</h2>
        </div>
        <p className={styles['cardSub']}>{t('suite.member.grant.subtitle')}</p>

        {operators.length === 0 ? (
          <p className={styles['noOperators']}>
            <Icon name="info" size={17} />
            {t('suite.member.grant.noOperators')}
          </p>
        ) : (
          <form
            className={styles['grantForm']}
            onSubmit={(event) => void handleSubmit(submitGrant)(event)}
            noValidate
          >
            <label className={styles['fieldWide']}>
              <span className={styles['label']}>{t('suite.member.grant.operatorLabel')}</span>
              <span className={styles['selectWrap']}>
                <select
                  className={styles['select']}
                  aria-label={t('suite.member.grant.operatorLabel')}
                  {...register('operatorId', { required: true })}
                >
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
                <Icon name="expand_more" size={18} className={styles['selectChevron']} />
              </span>
            </label>

            <label className={styles['field']}>
              <span className={styles['label']}>{t('suite.member.grant.roleLabel')}</span>
              <span className={styles['selectWrap']}>
                <select
                  className={styles['select']}
                  aria-label={t('suite.member.grant.roleLabel')}
                  {...register('role', { required: true })}
                >
                  {ROLES.map((role) => (
                    <option key={role} value={role}>
                      {t(`suite.member.role.${role}`)}
                    </option>
                  ))}
                </select>
                <Icon name="expand_more" size={18} className={styles['selectChevron']} />
              </span>
            </label>

            <button type="submit" className={styles['primaryBtn']} disabled={isGranting}>
              <Icon name="add" size={18} />
              {isGranting ? t('suite.member.grant.submitting') : t('suite.member.grant.submit')}
            </button>
          </form>
        )}

        {grantErrorKey !== null ? (
          <p className={styles['errorText']} role="alert">
            <Icon name="error" size={17} fill />
            {t(grantErrorKey)}
          </p>
        ) : null}
      </section>

      {/* Member action errors (role change / revoke) */}
      {memberActionErrorKey !== null ? (
        <p className={styles['errorText']} role="alert">
          <Icon name="error" size={17} fill />
          {t(memberActionErrorKey)}
        </p>
      ) : null}

      {/* Members list */}
      {members.length === 0 ? (
        <PlaceholderState icon="group" title={t('suite.member.empty')} />
      ) : (
        <div className={styles['table']}>
          <div className={styles['headRow']}>
            <span>{t('suite.member.column.operator')}</span>
            <span>{t('suite.member.column.role')}</span>
            <span className={styles['headActions']}>{t('suite.member.column.actions')}</span>
          </div>
          {members.map((member) => (
            <MemberRow
              key={member.membershipId}
              member={member}
              onChangeRole={changeRole}
              onRevoke={revokeMember}
              isChanging={isChanging}
              isRevoking={isRevoking}
              isLastAdmin={member.role === 'admin' && adminCount <= 1}
            />
          ))}
        </div>
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
  isLastAdmin: boolean
}

function MemberRow({
  member,
  onChangeRole,
  onRevoke,
  isChanging,
  isRevoking,
  isLastAdmin,
}: MemberRowProps) {
  const { t } = useTranslation()
  const [confirming, setConfirming] = useState(false)

  const initial = (member.email ?? '?').replace(/@.*$/, '').slice(0, 2).toUpperCase()

  return (
    <div className={styles['row']}>
      {/* operator identity */}
      <div className={styles['operator']}>
        <span className={styles['avatar']}>{initial}</span>
        <span className={styles['operatorMeta']}>
          {member.email !== null ? (
            <>
              <span className={styles['operatorName']}>{member.email.replace(/@.*$/, '')}</span>
              <span className={styles['operatorEmail']}>{member.email}</span>
            </>
          ) : (
            <span className={styles['stale']}>
              {t('suite.member.stale', { operatorId: member.operatorId })}
            </span>
          )}
        </span>
      </div>

      {/* role select */}
      <div className={styles['roleCell']}>
        <span className={styles['roleWrap']}>
          <select
            className={styles['roleSelect']}
            aria-label={t('suite.member.column.role')}
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
              <option key={role} value={role} disabled={isLastAdmin && role !== 'admin'}>
                {t(`suite.member.role.${role}`)}
              </option>
            ))}
          </select>
          <Icon name="expand_more" size={17} className={styles['roleChevron']} />
        </span>
        {isLastAdmin ? (
          <InfoHint
            text={t('suite.member.lastAdmin.hint')}
            label={t('suite.member.lastAdmin.label')}
          />
        ) : null}
      </div>

      {/* revoke with inline confirm */}
      <div className={styles['rowActions']}>
        {confirming ? (
          <>
            <span className={styles['confirmLabel']}>{t('suite.member.revoke.confirm')}</span>
            <button
              type="button"
              className={styles['confirmYes']}
              disabled={isRevoking}
              onClick={() => {
                onRevoke(member.membershipId)
              }}
            >
              {t('suite.member.revoke.action')}
            </button>
            <button
              type="button"
              className={styles['confirmNo']}
              onClick={() => {
                setConfirming(false)
              }}
            >
              {t('common.actions.cancel')}
            </button>
          </>
        ) : (
          <button
            type="button"
            className={styles['revokeBtn']}
            disabled={isLastAdmin}
            onClick={() => {
              setConfirming(true)
            }}
          >
            <Icon name="person_remove" size={16} />
            {t('suite.member.revoke.action')}
          </button>
        )}
      </div>
    </div>
  )
}

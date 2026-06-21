import { Link, useParams } from 'react-router-dom'
import { ActiveOrgIndicator } from '@/features/active-org-indicator'
import { MembershipConsole } from '@/features/membership-console'
import { useTranslation } from '@/shared/i18n'
import { LocaleSwitcher, PageHeader } from '@/shared/ui'

export function MembershipsPage() {
  const { t } = useTranslation()
  const { id } = useParams()

  return (
    <main>
      <PageHeader
        title={t('suite.member.title')}
        actions={
          <>
            <Link to="/admin/organizations">{t('suite.nav.organizations')}</Link>
            <ActiveOrgIndicator />
            <LocaleSwitcher />
          </>
        }
      />
      <p>{t('suite.member.description')}</p>
      {id !== undefined ? (
        <MembershipConsole organizationId={id} />
      ) : (
        <p>{t('common.error.notFound')}</p>
      )}
    </main>
  )
}

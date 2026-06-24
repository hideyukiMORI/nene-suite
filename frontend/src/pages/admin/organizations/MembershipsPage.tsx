import { Link, useParams } from 'react-router-dom'
import { MembershipConsole } from '@/features/membership-console'
import { useTranslation } from '@/shared/i18n'
import { PageHeader } from '@/shared/ui'

/** Content-only — the global chrome (header/nav) is owned by AppShell. */
export function MembershipsPage() {
  const { t } = useTranslation()
  const { id } = useParams()

  return (
    <>
      <PageHeader
        title={t('suite.member.title')}
        actions={<Link to="/admin/organizations">{t('suite.nav.organizations')}</Link>}
      />
      <p>{t('suite.member.description')}</p>
      {id !== undefined ? (
        <MembershipConsole organizationId={id} />
      ) : (
        <p>{t('common.error.notFound')}</p>
      )}
    </>
  )
}

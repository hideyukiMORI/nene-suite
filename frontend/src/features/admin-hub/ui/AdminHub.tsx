import { useSearchParams } from 'react-router-dom'
import { OrganizationConsole } from '@/features/organization-console'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon, PlaceholderState } from '@/shared/ui'
import styles from './admin-hub.module.css'
import { MembersTab } from './MembersTab'
import { OperatorsTab } from './OperatorsTab'

const TABS = ['organizations', 'members', 'operators', 'keys'] as const
type Tab = (typeof TABS)[number]

const TAB_LABEL: Record<Tab, MessageKey> = {
  organizations: 'suite.admin.tab.organizations',
  members: 'suite.admin.tab.members',
  operators: 'suite.admin.tab.operators',
  keys: 'suite.admin.tab.keys',
}

const TAB_ICON: Record<Tab, string> = {
  organizations: 'corporate_fare',
  members: 'group',
  operators: 'shield_person',
  keys: 'key',
}

function isTab(value: string | null): value is Tab {
  return value !== null && (TABS as readonly string[]).includes(value)
}

/** Superadmin admin hub — organizations / members / operators / federation keys. */
export function AdminHub() {
  const { t } = useTranslation()
  const [params, setParams] = useSearchParams()
  const tab: Tab = isTab(params.get('tab')) ? (params.get('tab') as Tab) : 'organizations'

  return (
    <div className={styles['root']}>
      <h1 className={styles['title']}>{t('suite.admin.title')}</h1>
      <p className={styles['subtitle']}>{t('suite.admin.subtitle')}</p>

      <div className={styles['tabs']} role="tablist">
        {TABS.map((value) => (
          <button
            key={value}
            type="button"
            role="tab"
            aria-selected={tab === value}
            data-active={tab === value}
            className={styles['tab']}
            onClick={() => {
              setParams({ tab: value })
            }}
          >
            <Icon name={TAB_ICON[value]} size={18} />
            {t(TAB_LABEL[value])}
          </button>
        ))}
      </div>

      <div className={styles['panel']}>
        {tab === 'organizations' ? <OrganizationConsole /> : null}
        {tab === 'members' ? <MembersTab /> : null}
        {tab === 'operators' ? <OperatorsTab /> : null}
        {tab === 'keys' ? (
          <PlaceholderState
            icon="key"
            title={t('suite.admin.tab.keys')}
            description={t('suite.admin.keys.placeholder')}
          />
        ) : null}
      </div>
    </div>
  )
}

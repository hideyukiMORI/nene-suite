import { useState } from 'react'
import { useTranslation, type MessageKey } from '@/shared/i18n'
import { Icon } from '@/shared/ui'
import styles from './account.module.css'
import { ProfileTab } from './ProfileTab'
import { SecurityTab } from './SecurityTab'
import { SessionsTab } from './SessionsTab'

const TABS = ['profile', 'security', 'sessions'] as const
type Tab = (typeof TABS)[number]

const TAB_LABEL: Record<Tab, MessageKey> = {
  profile: 'suite.account.tab.profile',
  security: 'suite.account.tab.security',
  sessions: 'suite.account.tab.sessions',
}

const TAB_ICON: Record<Tab, string> = {
  profile: 'person',
  security: 'shield',
  sessions: 'devices',
}

/** Account & security — profile / security / sessions tabs. */
export function AccountView() {
  const { t } = useTranslation()
  const [tab, setTab] = useState<Tab>('profile')

  return (
    <div className={styles['root']}>
      <h1 className={styles['title']}>{t('suite.account.title')}</h1>
      <p className={styles['subtitle']}>{t('suite.account.subtitle')}</p>

      <div className={styles['tabs']} role="tablist">
        {TABS.map((value) => (
          <button
            key={value}
            type="button"
            role="tab"
            aria-selected={tab === value}
            data-active={tab === value}
            className={styles['tabBtn']}
            onClick={() => {
              setTab(value)
            }}
          >
            <Icon name={TAB_ICON[value]} size={18} />
            {t(TAB_LABEL[value])}
          </button>
        ))}
      </div>

      <div>
        {tab === 'profile' ? <ProfileTab /> : null}
        {tab === 'security' ? <SecurityTab /> : null}
        {tab === 'sessions' ? <SessionsTab /> : null}
      </div>
    </div>
  )
}

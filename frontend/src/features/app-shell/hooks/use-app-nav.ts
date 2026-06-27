import { authStore } from '@/entities/auth'
import { useTranslation } from '@/shared/i18n'

export interface NavItem {
  id: string
  label: string
  /** Material Symbols ligature name. */
  icon: string
  path: string
  /** When set, the item is only shown to superadmin operators. */
  superadminOnly?: boolean
}

export interface NavGroup {
  id: string
  title: string
  items: NavItem[]
}

/**
 * Primary apex navigation, grouped for the sidebar shell (DESIGN-SYSTEM IA §1).
 * Order is fixed. `superadminOnly` items are dropped for non-superadmin operators,
 * and an emptied group is removed entirely.
 */
export function useAppNavGroups(): NavGroup[] {
  const { t } = useTranslation()
  const isSuperadmin = authStore.isSuperadmin()

  const groups: NavGroup[] = [
    {
      id: 'operations',
      title: t('suite.nav.group.operations'),
      items: [
        { id: 'home', label: t('suite.nav.home'), icon: 'home', path: '/' },
        { id: 'catalog', label: t('suite.nav.catalog'), icon: 'apps', path: '/catalog' },
        { id: 'install', label: t('suite.nav.install'), icon: 'download', path: '/install' },
      ],
    },
    {
      id: 'governance',
      title: t('suite.nav.group.governance'),
      items: [
        {
          id: 'admin',
          label: t('suite.nav.organizations'),
          icon: 'corporate_fare',
          path: '/admin/organizations',
          superadminOnly: true,
        },
        {
          id: 'audit',
          label: t('suite.nav.audit'),
          icon: 'receipt_long',
          path: '/admin/audit-events',
        },
        { id: 'settings', label: t('suite.nav.settings'), icon: 'settings', path: '/settings' },
      ],
    },
    {
      id: 'support',
      title: t('suite.nav.group.support'),
      items: [{ id: 'help', label: t('suite.nav.help'), icon: 'help', path: '/help' }],
    },
  ]

  return groups
    .map((group) => ({
      ...group,
      items: group.items.filter((item) => item.superadminOnly !== true || isSuperadmin),
    }))
    .filter((group) => group.items.length > 0)
}

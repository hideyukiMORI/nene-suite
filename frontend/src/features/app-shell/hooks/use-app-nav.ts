import { useTranslation } from '@/shared/i18n'

export interface NavItem {
  id: string
  label: string
  /** Material Symbols ligature name. */
  icon: string
  path: string
}

/** Primary apex navigation (DESIGN-SYSTEM IA §1). Order is fixed. */
export function useAppNav(): NavItem[] {
  const { t } = useTranslation()
  return [
    { id: 'home', label: t('suite.nav.home'), icon: 'home', path: '/' },
    { id: 'catalog', label: t('suite.nav.catalog'), icon: 'apps', path: '/catalog' },
    { id: 'audit', label: t('suite.nav.audit'), icon: 'receipt_long', path: '/admin/audit-events' },
    {
      id: 'admin',
      label: t('suite.nav.admin'),
      icon: 'admin_panel_settings',
      path: '/admin/organizations',
    },
    { id: 'settings', label: t('suite.nav.settings'), icon: 'settings', path: '/settings' },
    { id: 'help', label: t('suite.nav.help'), icon: 'help', path: '/help' },
  ]
}

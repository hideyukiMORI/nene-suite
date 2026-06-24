import { Outlet, useNavigate } from 'react-router-dom'
import { CommandPalette, useCommandPalette, type Command } from '@/features/command-palette'
import { useTranslation } from '@/shared/i18n'
import { useTheme } from '@/shared/ui'
import { useAppNav } from '../hooks/use-app-nav'
import { AppHeader } from './AppHeader'
import styles from './AppShell.module.css'

/** Authenticated layout: glass header + dot-grid main (renders the routed page). */
export function AppShell() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const nav = useAppNav()
  const { toggleTheme } = useTheme()
  const palette = useCommandPalette()

  const commands: Command[] = [
    ...nav.map(
      (item): Command => ({
        id: `nav:${item.id}`,
        group: 'nav',
        label: item.label,
        icon: item.icon,
        run: () => {
          void navigate(item.path)
        },
      }),
    ),
    {
      id: 'action:get-apps',
      group: 'action',
      label: t('suite.nav.getApps'),
      icon: 'add',
      run: () => {
        void navigate('/install')
      },
    },
    {
      id: 'action:toggle-theme',
      group: 'action',
      label: t('suite.theme.toggle'),
      icon: 'contrast',
      run: toggleTheme,
    },
  ]

  return (
    <div className={styles['shell']}>
      <AppHeader onOpenPalette={palette.open} />
      <main className={styles['main']}>
        <Outlet />
      </main>
      {palette.isOpen ? (
        <CommandPalette
          onClose={palette.close}
          commands={commands}
          labels={{
            title: t('suite.shell.commandPalette'),
            placeholder: t('suite.shell.search'),
            empty: t('suite.shell.commandPalette.empty'),
            navGroup: t('suite.shell.commandPalette.navGroup'),
            actionGroup: t('suite.shell.commandPalette.actionGroup'),
          }}
        />
      ) : null}
    </div>
  )
}

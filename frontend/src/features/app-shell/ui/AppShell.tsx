import { useState } from 'react'
import { Outlet, useNavigate } from 'react-router-dom'
import { CommandPalette, useCommandPalette, type Command } from '@/features/command-palette'
import { useTranslation } from '@/shared/i18n'
import { useTheme } from '@/shared/ui'
import { useAppNavGroups } from '../model/use-app-nav'
import { AppHeader } from './AppHeader'
import { AppSidebar } from './AppSidebar'
import styles from './AppShell.module.css'

/** Authenticated layout: left sidebar + a main column (top bar + routed page). */
export function AppShell() {
  const { t } = useTranslation()
  const navigate = useNavigate()
  const groups = useAppNavGroups()
  const { toggleTheme } = useTheme()
  const palette = useCommandPalette()
  const [menuOpen, setMenuOpen] = useState(false)

  const commands: Command[] = [
    ...groups
      .flatMap((group) => group.items)
      .map(
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
      <AppSidebar
        open={menuOpen}
        onClose={() => {
          setMenuOpen(false)
        }}
      />
      {menuOpen ? (
        <button
          type="button"
          className={styles['overlay']}
          aria-label={t('suite.nav.closeMenu')}
          onClick={() => {
            setMenuOpen(false)
          }}
        />
      ) : null}

      <div className={styles['maincol']}>
        <AppHeader
          onOpenPalette={palette.open}
          onOpenMenu={() => {
            setMenuOpen(true)
          }}
        />
        <main className={styles['main']}>
          <Outlet />
        </main>
      </div>

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

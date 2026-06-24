import { Icon } from './Icon'
import { useTheme } from './theme/use-theme'
import styles from './ThemeToggle.module.css'

/**
 * Light/dark toggle. Presentational primitive — the accessible label is passed
 * in (already localized) so shared/ui stays free of i18n lookups; only the
 * theme hook (a shared/ui concern) is consumed here.
 */
export function ThemeToggle({ label }: { label: string }) {
  const { theme, toggleTheme } = useTheme()
  return (
    <button
      type="button"
      className={styles['button']}
      onClick={toggleTheme}
      title={label}
      aria-label={label}
    >
      <Icon name={theme === 'dark' ? 'light_mode' : 'dark_mode'} size={20} />
    </button>
  )
}

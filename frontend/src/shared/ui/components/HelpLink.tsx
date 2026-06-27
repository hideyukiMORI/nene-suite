import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import { useTranslation } from '@/shared/i18n'
import { Icon } from '../Icon'
import styles from './HelpLink.module.css'

/**
 * "How to use this page" entry point for a screen header. Routes to the matching
 * help guide (`/help/{topic}`) — explicit navigation, no hover tooltip.
 */
export function HelpLink({ topic }: { readonly topic: string }): ReactNode {
  const { t } = useTranslation()
  return (
    <Link to={`/help/${topic}`} className={styles['root']}>
      <Icon name="help" size={16} />
      <span>{t('suite.help.pageLink')}</span>
    </Link>
  )
}

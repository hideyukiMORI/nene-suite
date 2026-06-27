import type { ReactNode } from 'react'
import { HelpHome, HelpLayout } from '@/features/help'

/** Help hub. Content-only — global chrome is in AppShell. */
export function HelpHomePage(): ReactNode {
  return (
    <HelpLayout>
      <HelpHome />
    </HelpLayout>
  )
}

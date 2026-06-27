import type { ReactNode } from 'react'
import { HelpGlossary, HelpLayout } from '@/features/help'

/** Glossary. Content-only — global chrome is in AppShell. */
export function HelpGlossaryPage(): ReactNode {
  return (
    <HelpLayout>
      <HelpGlossary />
    </HelpLayout>
  )
}

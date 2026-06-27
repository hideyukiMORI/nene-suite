import type { ReactNode } from 'react'
import { useParams } from 'react-router-dom'
import { HelpGuide, HelpLayout } from '@/features/help'

/** Single guide by slug. Content-only — global chrome is in AppShell. */
export function HelpGuidePage(): ReactNode {
  const { slug } = useParams()
  return (
    <HelpLayout>
      <HelpGuide slug={slug ?? ''} />
    </HelpLayout>
  )
}

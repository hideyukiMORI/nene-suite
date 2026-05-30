import { useSearchParams } from 'react-router-dom'
import { useCatalogApps, type CatalogApp } from '@/entities/catalog-app'
import {
  useAcceptDisclaimer,
  useCompleteInstallSession,
  useInstallSession,
  useStartInstallSession,
  useUpdateAppSelection,
  type InstallSession,
} from '@/entities/install-session'

export type WizardStep = 'apps' | 'disclaimer' | 'review' | 'complete'

const STEPS: readonly WizardStep[] = ['apps', 'disclaimer', 'review', 'complete']
const DISCLAIMER_VERSION = '2026-05-29'

function toStep(value: string | null): WizardStep {
  return value !== null && (STEPS as readonly string[]).includes(value)
    ? (value as WizardStep)
    : 'apps'
}

export interface UseInstallWizardResult {
  step: WizardStep
  sessionId: string | null
  session: InstallSession | undefined
  catalogApps: CatalogApp[]
  isStarting: boolean
  isMutating: boolean
  start: () => void
  selectApps: (ids: string[]) => void
  acceptDisclaimer: () => void
  complete: () => void
  goToStep: (step: WizardStep) => void
}

/**
 * Drives the installer wizard against a server-side install session. The session
 * id and current step live in the URL (?session=&step=) so refreshes resume the
 * wizard. The flow never completes client-side without a successful API call.
 */
export function useInstallWizard(): UseInstallWizardResult {
  const [searchParams, setSearchParams] = useSearchParams()
  const sessionId = searchParams.get('session')
  const step = toStep(searchParams.get('step'))

  const sessionQuery = useInstallSession(sessionId)
  const catalogQuery = useCatalogApps()
  const startMutation = useStartInstallSession()
  const selectionMutation = useUpdateAppSelection()
  const disclaimerMutation = useAcceptDisclaimer()
  const completeMutation = useCompleteInstallSession()

  const setStep = (next: WizardStep, sessionParam?: string): void => {
    const params = new URLSearchParams(searchParams)
    params.set('step', next)
    if (sessionParam !== undefined) {
      params.set('session', sessionParam)
    }
    setSearchParams(params)
  }

  const start = (): void => {
    startMutation.mutate(
      { tier: 'B' },
      {
        onSuccess: (session) => {
          setStep('apps', session.id)
        },
      },
    )
  }

  const selectApps = (ids: string[]): void => {
    if (sessionId === null) {
      return
    }
    selectionMutation.mutate(
      { installSessionId: sessionId, selectedApps: ids },
      {
        onSuccess: () => {
          setStep('disclaimer')
        },
      },
    )
  }

  const acceptDisclaimer = (): void => {
    if (sessionId === null) {
      return
    }
    disclaimerMutation.mutate(
      { installSessionId: sessionId, disclaimerVersion: DISCLAIMER_VERSION },
      {
        onSuccess: () => {
          setStep('review')
        },
      },
    )
  }

  const complete = (): void => {
    if (sessionId === null) {
      return
    }
    completeMutation.mutate(sessionId, {
      onSuccess: () => {
        setStep('complete')
      },
    })
  }

  return {
    step,
    sessionId,
    session: sessionQuery.data,
    catalogApps: catalogQuery.data ?? [],
    isStarting: startMutation.isPending,
    isMutating:
      selectionMutation.isPending || disclaimerMutation.isPending || completeMutation.isPending,
    start,
    selectApps,
    acceptDisclaimer,
    complete,
    goToStep: (next) => {
      setStep(next)
    },
  }
}

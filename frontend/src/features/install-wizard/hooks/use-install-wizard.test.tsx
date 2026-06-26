import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { beforeEach, describe, expect, it } from 'vitest'
import { resetInstallSessionState } from '@tests/msw/handlers/install-session'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useInstallWizard } from './use-install-wizard'

describe('useInstallWizard', () => {
  beforeEach(() => {
    resetInstallSessionState()
  })

  it('advances apps → database → disclaimer through the session', async () => {
    const { result } = renderHookWithProviders(() => useInstallWizard())

    expect(result.current.sessionId).toBeNull()
    expect(result.current.step).toBe('apps')

    act(() => {
      result.current.start()
    })

    await waitFor(() => {
      expect(result.current.sessionId).not.toBeNull()
    })

    act(() => {
      result.current.selectApps(['nene-invoice'])
    })

    await waitFor(() => {
      expect(result.current.step).toBe('database')
    })

    act(() => {
      result.current.setDatabaseTargets([
        { catalogId: 'nene-invoice', mode: 'adopt', server: 'legacy-db.internal', name: 'invoice_prod' },
      ])
    })

    await waitFor(() => {
      expect(result.current.step).toBe('disclaimer')
    })
  })
})

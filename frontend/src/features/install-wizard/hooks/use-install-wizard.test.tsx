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

  it('starts a session then advances to the disclaimer step after selecting apps', async () => {
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
      expect(result.current.step).toBe('disclaimer')
    })
  })
})

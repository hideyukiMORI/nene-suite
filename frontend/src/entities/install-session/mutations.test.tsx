import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { beforeEach, describe, expect, it } from 'vitest'
import { resetInstallSessionState } from '@tests/msw/handlers/install-session'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import type { InstallSession } from './model'
import {
  useAcceptDisclaimer,
  useCompleteInstallSession,
  useFailInstallSession,
  useSetDatabaseTargets,
  useStartInstallSession,
  useUpdateAppSelection,
} from './mutations'

const SESSION_ID = '01J8XR4ZS6Q9V2H7K3N5M0B8TC'

/** Create the in-memory install session via the start hook, returning its id. */
async function startSession(): Promise<string> {
  const { result } = renderHookWithProviders(() => useStartInstallSession())
  act(() => {
    result.current.mutate({ tier: 'B' })
  })
  await waitFor(() => {
    expect(result.current.isSuccess).toBe(true)
  })
  return result.current.data?.id ?? ''
}

describe('install-session mutations', () => {
  beforeEach(() => {
    resetInstallSessionState()
  })

  it('starts a session and maps the DTO into the domain model', async () => {
    const { result } = renderHookWithProviders(() => useStartInstallSession())

    act(() => {
      result.current.mutate({ tier: 'B' })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    const session = result.current.data as InstallSession
    expect(session.id).toBe(SESSION_ID)
    expect(session.status).toBe('in_progress')
    expect(session.tier).toBe('B')
    // mapper fills optional snapshot fields with null, never undefined.
    expect(session.disclaimerAcceptedAt).toBeNull()
    expect(session.completedAt).toBeNull()
    expect(session.databaseTargets).toEqual([])
  })

  it('updates the app selection', async () => {
    const installSessionId = await startSession()
    const { result } = renderHookWithProviders(() => useUpdateAppSelection())

    act(() => {
      result.current.mutate({ installSessionId, selectedApps: ['nene-invoice', 'nene-vault'] })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    expect(result.current.data?.selectedApps).toEqual(['nene-invoice', 'nene-vault'])
  })

  it('sets database targets and normalizes optional server/name to null', async () => {
    const installSessionId = await startSession()
    const { result } = renderHookWithProviders(() => useSetDatabaseTargets())

    act(() => {
      result.current.mutate({
        installSessionId,
        targets: [
          { catalogId: 'nene-invoice', mode: 'provision' },
          { catalogId: 'nene-vault', mode: 'adopt', server: 'db.internal', name: 'vault_db' },
        ],
      })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    const targets = result.current.data?.databaseTargets ?? []
    expect(targets).toHaveLength(2)
    // provision target: absent server/name become null (mapper `?? null`).
    expect(targets[0]).toEqual({
      catalogId: 'nene-invoice',
      mode: 'provision',
      server: null,
      name: null,
    })
    expect(targets[1]).toEqual({
      catalogId: 'nene-vault',
      mode: 'adopt',
      server: 'db.internal',
      name: 'vault_db',
    })
  })

  it('accepts the disclaimer', async () => {
    const installSessionId = await startSession()
    const { result } = renderHookWithProviders(() => useAcceptDisclaimer())

    act(() => {
      result.current.mutate({ installSessionId, disclaimerVersion: '2026-06-01' })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    expect(result.current.data?.disclaimerAccepted).toBe(true)
    expect(result.current.data?.disclaimerAcceptedAt).not.toBeNull()
  })

  it('completes the session and surfaces the manifest id', async () => {
    const installSessionId = await startSession()
    const { result } = renderHookWithProviders(() => useCompleteInstallSession())

    act(() => {
      result.current.mutate(installSessionId)
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    expect(result.current.data?.status).toBe('completed')
    expect(result.current.data?.installManifestId).not.toBeNull()
  })

  it('fails the session with a failure code', async () => {
    const installSessionId = await startSession()
    const { result } = renderHookWithProviders(() => useFailInstallSession())

    act(() => {
      result.current.mutate({ installSessionId, failureCode: 'db_unreachable' })
    })

    await waitFor(() => {
      expect(result.current.isSuccess).toBe(true)
    })
    expect(result.current.data?.status).toBe('failed')
    expect(result.current.data?.failureCode).toBe('db_unreachable')
  })
})

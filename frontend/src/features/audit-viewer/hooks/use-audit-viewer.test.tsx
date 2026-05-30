import { act } from 'react'
import { waitFor } from '@testing-library/react'
import { describe, expect, it } from 'vitest'
import { renderHookWithProviders } from '@tests/render/render-with-providers'
import { useAuditViewer } from './use-audit-viewer'

describe('useAuditViewer', () => {
  it('loads the first page then appends the next page', async () => {
    const { result } = renderHookWithProviders(() => useAuditViewer())

    await waitFor(() => {
      expect(result.current.isLoading).toBe(false)
    })

    expect(result.current.events).toHaveLength(2)
    expect(result.current.events[0]?.action).toBe('install_session.completed')
    expect(result.current.hasMore).toBe(true)

    act(() => {
      result.current.loadMore()
    })

    await waitFor(() => {
      expect(result.current.events).toHaveLength(3)
    })
    expect(result.current.hasMore).toBe(false)
    expect(result.current.events[2]?.action).toBe('install_session.started')
  })
})

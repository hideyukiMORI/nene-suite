import { render, screen, waitFor } from '@testing-library/react'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { useConsoleStream } from './use-console-stream'

function Probe() {
  const ref = useConsoleStream()
  return <span data-testid="stream" ref={ref} />
}

afterEach(() => {
  vi.unstubAllGlobals()
})

describe('useConsoleStream', () => {
  it('shows the first log line statically under reduced motion', async () => {
    vi.stubGlobal('matchMedia', vi.fn().mockReturnValue({ matches: true }))

    render(<Probe />)

    await waitFor(() => {
      expect(screen.getByTestId('stream').textContent).toBe('auth.session · endpoint ready')
    })
  })
})

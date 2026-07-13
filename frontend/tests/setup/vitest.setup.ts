import '@testing-library/jest-dom/vitest'
import { cleanup } from '@testing-library/react'
import { afterAll, afterEach, beforeAll } from 'vitest'
import { mswServer } from '@tests/msw/server'

beforeAll(() => {
  mswServer.listen({ onUnhandledRequest: 'error' })
})

afterEach(() => {
  mswServer.resetHandlers()
  cleanup()
  localStorage.clear()
  sessionStorage.clear()
})

afterAll(() => {
  mswServer.close()
})

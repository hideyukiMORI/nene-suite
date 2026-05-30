import { setupServer } from 'msw/node'
import { authHandlers } from './handlers/auth'

export const mswServer = setupServer(...authHandlers)

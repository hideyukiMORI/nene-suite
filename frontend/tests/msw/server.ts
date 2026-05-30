import { setupServer } from 'msw/node'
import { authHandlers } from './handlers/auth'
import { installedAppHandlers } from './handlers/installed-app'

export const mswServer = setupServer(...authHandlers, ...installedAppHandlers)

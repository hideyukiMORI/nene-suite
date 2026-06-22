import { setupServer } from 'msw/node'
import { authHandlers } from './handlers/auth'
import { catalogAppHandlers } from './handlers/catalog-app'
import { installSessionHandlers } from './handlers/install-session'
import { installedAppHandlers } from './handlers/installed-app'
import { membershipHandlers } from './handlers/membership'
import { operatorHandlers } from './handlers/operator'
import { organizationHandlers } from './handlers/organization'
import { suiteAuditEventHandlers } from './handlers/suite-audit-event'

export const mswServer = setupServer(
  ...authHandlers,
  ...installedAppHandlers,
  ...catalogAppHandlers,
  ...installSessionHandlers,
  ...suiteAuditEventHandlers,
  ...organizationHandlers,
  ...membershipHandlers,
  ...operatorHandlers,
)

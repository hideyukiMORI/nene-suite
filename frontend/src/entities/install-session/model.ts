export type InstallSessionStatus = 'in_progress' | 'completed' | 'failed'

export interface DatabaseTarget {
  catalogId: string
  mode: 'provision' | 'adopt'
  /** Non-secret host / label; null = suite default server. */
  server: string | null
  /** Existing database name (adopt only); null = suite convention. */
  name: string | null
}

export interface InstallSession {
  id: string
  suiteId: string
  status: InstallSessionStatus
  tier: string
  catalogRevision: number
  selectedApps: string[]
  databaseTargets: DatabaseTarget[]
  disclaimerAccepted: boolean
  disclaimerAcceptedAt: string | null
  orgExternalId: string | null
  orgDisplayName: string | null
  installManifestId: string | null
  failureCode: string | null
  createdAt: string
  updatedAt: string
  completedAt: string | null
}

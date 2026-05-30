export type InstallSessionStatus = 'in_progress' | 'completed' | 'failed'

export interface InstallSession {
  id: string
  suiteId: string
  status: InstallSessionStatus
  tier: string
  catalogRevision: number
  selectedApps: string[]
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

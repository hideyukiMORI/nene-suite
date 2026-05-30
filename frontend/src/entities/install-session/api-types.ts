// Wire DTOs for the installer wizard (docs/openapi/openapi.yaml InstallSession).

export type InstallSessionStatusDto = 'in_progress' | 'completed' | 'failed'

export interface InstallSessionDto {
  id: string
  suiteId: string
  status: InstallSessionStatusDto
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

export interface StartInstallSessionRequestDto {
  tier: string
  selectedApps?: string[]
  orgDisplayName?: string
}

export interface UpdateAppSelectionRequestDto {
  selectedApps: string[]
}

export interface AcceptDisclaimerRequestDto {
  disclaimerVersion: string
  acceptedLabel?: string
}

export interface FailInstallSessionRequestDto {
  failureCode: string
  reason?: string
}

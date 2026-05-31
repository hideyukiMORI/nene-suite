// Wire DTOs for the installer wizard — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type InstallSessionStatusDto = components['schemas']['InstallSessionStatus']
export type InstallSessionDto = components['schemas']['InstallSession']
export type StartInstallSessionRequestDto = components['schemas']['StartInstallSessionRequest']
export type UpdateAppSelectionRequestDto = components['schemas']['UpdateAppSelectionRequest']
export type AcceptDisclaimerRequestDto = components['schemas']['AcceptDisclaimerRequest']
export type FailInstallSessionRequestDto = components['schemas']['FailInstallSessionRequest']

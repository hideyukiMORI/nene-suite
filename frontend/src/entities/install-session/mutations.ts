import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type { InstallSessionDto, StartInstallSessionRequestDto } from './api-types'
import { toInstallSession } from './mapper'
import type { InstallSession } from './model'
import { installSessionKeys } from './query-keys'

function useSessionWriter<TInput>(
  send: (input: TInput) => Promise<InstallSessionDto>,
): UseMutationResult<InstallSession, AppError, TInput> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input: TInput) => toInstallSession(await send(input)),
    onSuccess: (session) => {
      queryClient.setQueryData(installSessionKeys.detail(session.id), session)
    },
  })
}

export function useStartInstallSession(): UseMutationResult<
  InstallSession,
  AppError,
  StartInstallSessionRequestDto
> {
  return useSessionWriter((input) =>
    apiClient.post<InstallSessionDto>('/api/v1/install-sessions', input),
  )
}

export interface UpdateAppSelectionInput {
  installSessionId: string
  selectedApps: string[]
}

export function useUpdateAppSelection(): UseMutationResult<
  InstallSession,
  AppError,
  UpdateAppSelectionInput
> {
  return useSessionWriter(({ installSessionId, selectedApps }) =>
    apiClient.put<InstallSessionDto>(`/api/v1/install-sessions/${installSessionId}/app-selection`, {
      selectedApps,
    }),
  )
}

/** One app's database target choice (ADR 0022 mode A). `server`/`name` apply to adopt. */
export interface DatabaseTargetInput {
  catalogId: string
  mode: 'provision' | 'adopt'
  server?: string
  name?: string
}

export interface SetDatabaseTargetsInput {
  installSessionId: string
  targets: DatabaseTargetInput[]
}

export function useSetDatabaseTargets(): UseMutationResult<
  InstallSession,
  AppError,
  SetDatabaseTargetsInput
> {
  return useSessionWriter(({ installSessionId, targets }) =>
    apiClient.put<InstallSessionDto>(
      `/api/v1/install-sessions/${installSessionId}/database-targets`,
      { targets },
    ),
  )
}

export interface AcceptDisclaimerInput {
  installSessionId: string
  disclaimerVersion: string
  acceptedLabel?: string
}

export function useAcceptDisclaimer(): UseMutationResult<
  InstallSession,
  AppError,
  AcceptDisclaimerInput
> {
  return useSessionWriter(({ installSessionId, disclaimerVersion, acceptedLabel }) =>
    apiClient.post<InstallSessionDto>(
      `/api/v1/install-sessions/${installSessionId}/disclaimer-acceptance`,
      acceptedLabel !== undefined ? { disclaimerVersion, acceptedLabel } : { disclaimerVersion },
    ),
  )
}

export function useCompleteInstallSession(): UseMutationResult<InstallSession, AppError, string> {
  return useSessionWriter((installSessionId: string) =>
    apiClient.post<InstallSessionDto>(`/api/v1/install-sessions/${installSessionId}/complete`, {}),
  )
}

export interface FailInstallSessionInput {
  installSessionId: string
  failureCode: string
  reason?: string
}

export function useFailInstallSession(): UseMutationResult<
  InstallSession,
  AppError,
  FailInstallSessionInput
> {
  return useSessionWriter(({ installSessionId, failureCode, reason }) =>
    apiClient.post<InstallSessionDto>(
      `/api/v1/install-sessions/${installSessionId}/fail`,
      reason !== undefined ? { failureCode, reason } : { failureCode },
    ),
  )
}

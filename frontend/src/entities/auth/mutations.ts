import { useMutation, useQueryClient, type UseMutationResult } from '@tanstack/react-query'
import { apiClient, AppError } from '@/shared/api/client'
import type {
  AuthSessionDto,
  CreateAuthSessionRequestDto,
  SwitchActiveOrganizationRequestDto,
} from './api-types'
import { toAuthSession } from './mapper'
import { authStore, type AuthSession } from './model'

/** Logs an operator in (POST /api/v1/auth/session) and persists the session. */
export function useSignIn(): UseMutationResult<AuthSession, AppError, CreateAuthSessionRequestDto> {
  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.post<AuthSessionDto>('/api/v1/auth/session', input)
      const session = toAuthSession(dto)
      authStore.setSession(session)
      return session
    },
  })
}

/** Logs the operator out (DELETE /api/v1/auth/session) and clears the session. */
export function useSignOut(): UseMutationResult<void, AppError, void> {
  return useMutation({
    mutationFn: async () => {
      await apiClient.delete('/api/v1/auth/session')
      authStore.clearSession()
    },
  })
}

/**
 * Switches the session's active organization (PUT /api/v1/auth/session/active-organization).
 * Persists the re-issued session, then invalidates every query so cached data re-fetches under
 * the new org-scoped token.
 */
export function useSwitchActiveOrganization(): UseMutationResult<
  AuthSession,
  AppError,
  SwitchActiveOrganizationRequestDto
> {
  const queryClient = useQueryClient()

  return useMutation({
    mutationFn: async (input) => {
      const dto = await apiClient.put<AuthSessionDto>(
        '/api/v1/auth/session/active-organization',
        input,
      )
      const session = toAuthSession(dto)
      authStore.setSession(session)
      return session
    },
    onSuccess: () => {
      void queryClient.invalidateQueries()
    },
  })
}

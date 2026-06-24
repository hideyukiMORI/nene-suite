import { useNavigate } from 'react-router-dom'
import { useSignIn as useSignInMutation } from '@/entities/auth'

export interface SignInCredentials {
  email: string
  password: string
}

export interface UseSignInResult {
  submit: (credentials: SignInCredentials) => void
  isPending: boolean
  isInvalidCredentials: boolean
  isRateLimited: boolean
  hasError: boolean
}

/**
 * Drives the apex sign-in form: runs the entity mutation and, on success,
 * navigates to the apex home. Surfaces invalid-credentials (401) distinctly so
 * the form can show the localized message.
 */
export function useSignIn(redirectTo = '/'): UseSignInResult {
  const navigate = useNavigate()
  const mutation = useSignInMutation()

  const submit = (credentials: SignInCredentials): void => {
    mutation.mutate(credentials, {
      onSuccess: () => {
        void navigate(redirectTo, { replace: true })
      },
    })
  }

  return {
    submit,
    isPending: mutation.isPending,
    isInvalidCredentials: mutation.error?.status === 401,
    isRateLimited: mutation.error?.status === 429,
    hasError: mutation.error !== null,
  }
}

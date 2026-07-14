import {
  createNene2Transport,
  isNene2ClientError,
  type ProblemDetailsDocument,
  type TokenStore,
} from '@hideyukimori/nene2-client'
import { authStore } from '@/entities/auth/model'
import { AppError, type ProblemDetails } from '@/shared/api/errors'
import { env } from '@/shared/config/env'

/**
 * Adapts the existing `authStore` (an `AuthSession` JSON blob in sessionStorage,
 * `entities/auth/model.ts` — sessionStorage-ified in #375/#376) to the transport's
 * minimal {@link TokenStore} contract. We deliberately do NOT switch to the
 * package's `createSessionTokenStore`: the session object carries more than the
 * bearer token (operator, org context, role, superadmin flag) and the storage
 * key/shape is a settled contract (#375/#376). `clearToken` clears the whole
 * session, matching the pre-migration `authStore.clearSession()` behavior on 401.
 */
const authTokenStore: TokenStore = {
  getToken: () => authStore.getToken(),
  clearToken: () => {
    authStore.clearSession()
  },
}

/**
 * On an expired/invalid apex token, bounce to login. Login attempts
 * (POST /auth/session) never reach here: the transport only fires
 * `onUnauthorized` when a token was attached to the failed request, and a
 * login attempt has none yet, so invalid-credentials errors surface on the
 * form instead of redirecting (same behavior as before the migration).
 */
function handleUnauthorized(): void {
  window.location.href = '/login'
}

const transport = createNene2Transport({
  baseUrl: env.apiBaseUrl,
  tokenStore: authTokenStore,
  credentials: 'include',
  onUnauthorized: handleUnauthorized,
  // A thin pass-through, not `globalThis.fetch` directly: the transport resolves
  // and binds its `fetch` once, at module-load time. Passing the global directly
  // would freeze that early reference — MSW (our test HTTP mock, see
  // tests/msw/server.ts) patches `globalThis.fetch` later, in `beforeAll`, so the
  // frozen pre-patch reference would bypass it entirely. This wrapper re-reads
  // `globalThis.fetch` on every call instead, exactly like the pre-migration
  // client's bare `fetch(...)` call did.
  fetch: (input, init) => globalThis.fetch(input, init),
})

/** Maps a parsed Problem Details document (already validated by the transport) to `AppError`. */
function problemToAppError(problem: ProblemDetailsDocument): AppError {
  return new AppError({
    type: problem.type,
    title: problem.title,
    status: problem.status,
    ...(typeof problem.detail === 'string' ? { detail: problem.detail } : {}),
    ...(typeof problem.instance === 'string' ? { instance: problem.instance } : {}),
    ...(Array.isArray(problem.errors)
      ? { errors: problem.errors as NonNullable<ProblemDetails['errors']> }
      : {}),
  })
}

/**
 * Maps any transport failure to the product's `AppError` (unchanged public shape).
 * The transport already guarantees non-2xx errors never surface as raw HTML.
 */
async function withAppError<T>(run: () => Promise<T>): Promise<T> {
  try {
    return await run()
  } catch (error) {
    if (isNene2ClientError(error)) {
      if (error.problem !== undefined) {
        throw problemToAppError(error.problem)
      }
      throw new AppError({
        type: 'about:blank',
        title: error.message || 'Request failed',
        status: error.status,
      })
    }
    throw error
  }
}

export const apiClient = {
  get<T>(path: string, signal?: AbortSignal): Promise<T> {
    return withAppError(() => transport.get<T>(path, signal !== undefined ? { signal } : {}))
  },
  post<T>(path: string, body: unknown): Promise<T> {
    return withAppError(() => transport.post<T>(path, body))
  },
  put<T>(path: string, body: unknown): Promise<T> {
    return withAppError(() => transport.put<T>(path, body))
  },
  patch<T>(path: string, body: unknown): Promise<T> {
    return withAppError(() => transport.patch<T>(path, body))
  },
  delete(path: string): Promise<undefined> {
    return withAppError(() => transport.delete<undefined>(path))
  },
}

export { AppError }

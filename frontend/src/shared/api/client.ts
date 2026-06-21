import { authStore } from '@/entities/auth/model'
import { AppError, parseProblemDetails } from '@/shared/api/errors'
import { env } from '@/shared/config/env'

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE'

interface RequestOptions {
  method?: HttpMethod
  body?: unknown
  signal?: AbortSignal
}

/**
 * On an expired/invalid apex token, clear the session and bounce to login.
 * Login attempts (POST /auth/session) are excluded so invalid-credentials errors
 * surface on the form instead of redirecting.
 */
function handleErrorResponse(response: Response, path: string): void {
  if (response.status === 401 && !path.includes('/auth/session')) {
    authStore.clearSession()
    window.location.href = '/login'
  }
}

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const base = env.apiBaseUrl.replace(/\/$/, '')
  const headers: Record<string, string> = {}
  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }
  const token = authStore.getToken()
  if (token !== null) {
    headers['Authorization'] = `Bearer ${token}`
  }

  const response = await fetch(`${base}${path}`, {
    method: options.method ?? 'GET',
    headers,
    credentials: 'include',
    ...(options.body !== undefined ? { body: JSON.stringify(options.body) } : {}),
    ...(options.signal !== undefined ? { signal: options.signal } : {}),
  })

  if (!response.ok) {
    handleErrorResponse(response, path)
    throw await parseProblemDetails(response)
  }

  if (response.status === 204) {
    return undefined as T
  }

  return (await response.json()) as T
}

export const apiClient = {
  get<T>(path: string, signal?: AbortSignal): Promise<T> {
    return request<T>(path, signal !== undefined ? { signal } : {})
  },
  post<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'POST', body })
  },
  put<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'PUT', body })
  },
  patch<T>(path: string, body: unknown): Promise<T> {
    return request<T>(path, { method: 'PATCH', body })
  },
  delete(path: string): Promise<undefined> {
    return request<undefined>(path, { method: 'DELETE' })
  },
}

export { AppError }

import type { AppError } from '@/shared/api/client'
import type { MessageKey } from './translate'

export function mapProblemDetailsToMessageKey(error: AppError): MessageKey | null {
  switch (error.status) {
    case 401:
      return 'common.error.unauthorized'
    case 403:
      return 'common.error.forbidden'
    case 404:
      return 'common.error.notFound'
    case 409:
      return 'common.error.conflict'
    case 422:
      return 'common.error.validation'
    case 429:
      return 'common.error.rateLimit'
    default:
      return error.status >= 500 ? 'common.error.serverError' : null
  }
}

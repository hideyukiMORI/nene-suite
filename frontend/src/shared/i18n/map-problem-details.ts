import type { AppError } from '@/shared/api/client'
import type { MessageKey } from './translate'

const PROBLEM_BASE = 'https://nene-suite.dev/problems/'

export function mapProblemDetailsToMessageKey(error: AppError): MessageKey | null {
  // Domain-specific problem types take precedence over the generic status mapping.
  switch (error.type) {
    case `${PROBLEM_BASE}organization-slug-conflict`:
      return 'suite.org.error.slugConflict'
    case `${PROBLEM_BASE}organization-validation-failed`:
      return 'suite.org.error.validation'
    case `${PROBLEM_BASE}organization-not-found`:
      return 'suite.org.error.notFound'
    case `${PROBLEM_BASE}membership-conflict`:
      return 'suite.member.error.conflict'
    case `${PROBLEM_BASE}membership-invariant`:
      return 'suite.member.error.invariant'
    case `${PROBLEM_BASE}membership-validation-failed`:
      return 'suite.member.error.validation'
    case `${PROBLEM_BASE}membership-not-found`:
      return 'suite.member.error.notFound'
    default:
      break
  }

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

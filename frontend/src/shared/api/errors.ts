export interface ValidationError {
  field: string
  code: string
  detail?: string
}

export interface ProblemDetails {
  type: string
  title: string
  status: number
  detail?: string
  instance?: string
  errors?: readonly ValidationError[]
}

/**
 * RFC 9457 Problem Details surfaced as a typed error. API metadata (title/detail)
 * stays in English; UI maps it via shared/i18n/map-problem-details.
 */
export class AppError extends Error {
  readonly status: number
  readonly type: string
  readonly title: string
  readonly detail?: string
  readonly instance?: string
  readonly errors?: readonly ValidationError[]

  constructor(problem: ProblemDetails) {
    super(problem.title)
    this.name = 'AppError'
    this.status = problem.status
    this.type = problem.type
    this.title = problem.title
    if (problem.detail !== undefined) {
      this.detail = problem.detail
    }
    if (problem.instance !== undefined) {
      this.instance = problem.instance
    }
    if (problem.errors !== undefined) {
      this.errors = problem.errors
    }
  }

  get isRetryable(): boolean {
    return this.status >= 500 || this.status === 429
  }
}

export async function parseProblemDetails(response: Response): Promise<AppError> {
  try {
    const body = (await response.json()) as ProblemDetails
    return new AppError({ ...body, status: body.status })
  } catch {
    return new AppError({
      type: 'about:blank',
      title: response.statusText || 'Request failed',
      status: response.status,
    })
  }
}

import { describe, expect, it } from 'vitest'
import { AppError, parseProblemDetails, type ProblemDetails } from './errors'

function problem(overrides: Partial<ProblemDetails> = {}): ProblemDetails {
  return {
    type: 'https://nene-suite.dev/problems/validation',
    title: 'Validation failed',
    status: 422,
    ...overrides,
  }
}

describe('AppError', () => {
  it('is a real Error subclass carrying the RFC 9457 core fields', () => {
    const error = new AppError(problem())

    expect(error).toBeInstanceOf(Error)
    expect(error).toBeInstanceOf(AppError)
    expect(error.name).toBe('AppError')
    // super(title) — the human-readable message is the Problem title.
    expect(error.message).toBe('Validation failed')
    expect(error.status).toBe(422)
    expect(error.type).toBe('https://nene-suite.dev/problems/validation')
    expect(error.title).toBe('Validation failed')
  })

  it('copies optional detail / instance / errors when present', () => {
    const errors = [{ field: 'email', code: 'required' }] as const
    const error = new AppError(
      problem({ detail: 'The email field is required.', instance: '/install/sessions', errors }),
    )

    expect(error.detail).toBe('The email field is required.')
    expect(error.instance).toBe('/install/sessions')
    expect(error.errors).toEqual(errors)
  })

  it('leaves absent optional fields undefined', () => {
    const error = new AppError(problem())

    expect(error.detail).toBeUndefined()
    expect(error.instance).toBeUndefined()
    expect(error.errors).toBeUndefined()
  })

  it('can be thrown and caught as an AppError', () => {
    const throwing = () => {
      throw new AppError(problem({ status: 500, title: 'Boom' }))
    }

    expect(throwing).toThrow(AppError)
    expect(throwing).toThrow('Boom')
  })

  describe('isRetryable', () => {
    it.each([500, 502, 503, 429])('is true for retryable status %i', (status) => {
      expect(new AppError(problem({ status })).isRetryable).toBe(true)
    })

    it.each([400, 401, 403, 404, 409, 422])('is false for client status %i', (status) => {
      expect(new AppError(problem({ status })).isRetryable).toBe(false)
    })
  })
})

describe('parseProblemDetails', () => {
  it('builds an AppError from a JSON Problem Details body', async () => {
    const body = problem({
      status: 422,
      detail: 'One field is invalid.',
      errors: [{ field: 'password', code: 'too_short', detail: 'min 12' }],
    })
    const response = new Response(JSON.stringify(body), { status: 422 })

    const error = await parseProblemDetails(response)

    expect(error).toBeInstanceOf(AppError)
    expect(error.status).toBe(422)
    expect(error.title).toBe('Validation failed')
    expect(error.detail).toBe('One field is invalid.')
    expect(error.errors).toHaveLength(1)
    expect(error.errors?.[0]?.field).toBe('password')
  })

  it('takes the status from the JSON body, not the HTTP response', async () => {
    // Body says 409 even though the transport reports 200 — the body is authoritative.
    const response = new Response(JSON.stringify(problem({ status: 409 })), { status: 200 })

    const error = await parseProblemDetails(response)

    expect(error.status).toBe(409)
  })

  it('falls back to statusText when the body is not JSON', async () => {
    const response = new Response('<html>Bad Gateway</html>', {
      status: 502,
      statusText: 'Bad Gateway',
    })

    const error = await parseProblemDetails(response)

    expect(error.type).toBe('about:blank')
    expect(error.title).toBe('Bad Gateway')
    expect(error.status).toBe(502)
  })

  it('falls back to a generic title when the body is unparseable and statusText is empty', async () => {
    const response = new Response('not json', { status: 500, statusText: '' })

    const error = await parseProblemDetails(response)

    expect(error.title).toBe('Request failed')
    expect(error.status).toBe(500)
  })
})

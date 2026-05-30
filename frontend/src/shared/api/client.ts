export class AppError extends Error {
  constructor(
    public readonly status: number,
    message: string,
  ) {
    super(message)
    this.name = 'AppError'
  }

  get isRetryable(): boolean {
    return this.status >= 500
  }
}

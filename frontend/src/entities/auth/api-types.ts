// Wire DTOs for the apex auth session (docs/openapi/openapi.yaml).

export interface OperatorDto {
  id: string
  email: string
  displayName: string | null
}

export interface CreateAuthSessionRequestDto {
  email: string
  password: string
}

export interface AuthSessionDto {
  token: string
  expiresAt: string
  operator: OperatorDto
}

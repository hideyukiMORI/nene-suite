// Wire DTOs for the orchestration audit trail — derived from docs/openapi/openapi.yaml via schema.gen.ts.
import type { components } from '@/shared/api/schema.gen'

export type SuiteAuditEventDto = components['schemas']['SuiteAuditEvent']
export type SuiteAuditEventListDto = components['schemas']['SuiteAuditEventList']

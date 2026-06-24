import { z } from 'zod'

const envSchema = z.object({
  VITE_API_BASE_URL: z.string().optional(),
  // Build-time edition label for the shell badge (the authoritative edition is
  // the backend NENE_SUITE_EDITION; this only drives chrome). Default oss.
  VITE_NENE_SUITE_EDITION: z.enum(['oss', 'hosted']).optional(),
})

const parsed = envSchema.parse({
  VITE_API_BASE_URL: import.meta.env['VITE_API_BASE_URL'] as string | undefined,
  VITE_NENE_SUITE_EDITION: import.meta.env['VITE_NENE_SUITE_EDITION'] as string | undefined,
})

export const env = {
  /** Empty string uses same-origin (Vite dev proxy → suite API). */
  apiBaseUrl: parsed.VITE_API_BASE_URL ?? '',
  /** Shell edition badge: 'oss' | 'hosted'. */
  edition: parsed.VITE_NENE_SUITE_EDITION ?? 'oss',
} as const

import { http, HttpResponse } from 'msw'

// Default: Origin not configured (no URL / trust anchor) — the realistic unconfigured state.
// Tests that exercise the wired surfaces override these with `mswServer.use(...)`.
export const originHandlers = [
  http.get('/api/v1/origin/updates', () => HttpResponse.json({ available: false, updates: [] })),
  http.get('/api/v1/origin/announcements', () => HttpResponse.json({ available: false, feeds: [] })),
  http.get('/api/v1/origin/house-ads', () => HttpResponse.json({ available: false, feeds: [] })),
]

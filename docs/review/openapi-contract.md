# OpenAPI Contract Self-Review

Use when changing `docs/openapi/openapi.yaml` or HTTP response shapes.

Sources: NENE2 `docs/integrations/openapi.md`, [`../development/backend-standards.md`](../development/backend-standards.md).

## Checklist

- [ ] OpenAPI 3.1; every shipped path has `operationId`.
- [ ] Success response schema + example; Problem Details on error statuses.
- [ ] Problem `type` uses `https://nene-suite.dev/problems/{slug}`.
- [ ] `composer openapi` passes (Phase 1+).
- [ ] Contract tests updated in `tests/OpenApi/` when applicable.
- [ ] Frontend codegen run if `frontend/` exists.

Mark `N/A` when OpenAPI untouched.

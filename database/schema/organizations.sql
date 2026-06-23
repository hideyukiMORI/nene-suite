-- GROUP: Tenancy
-- TABLE: Suite tenancy registry (SSOT). external_id is the federation UUID propagated to sibling apps; rows are status-flagged, never hard-deleted.
CREATE TABLE organizations (
  id CHAR(26) NOT NULL PRIMARY KEY,          -- ULID primary key.
  external_id CHAR(26) NOT NULL,             -- Federation UUID (org_external_id in JWT claims), propagated to sibling apps. Unique.
  name VARCHAR(320) NOT NULL,                -- Organization display name (operator-settable).
  slug VARCHAR(160) NOT NULL,                -- URL-safe identifier for per-organization resolution (path/subdomain). Unique.
  status VARCHAR(32) NOT NULL,               -- Lifecycle state: active | disabled. Never hard-deleted (ADR 0012).
  created_at VARCHAR(32) NOT NULL,           -- Record creation time, ISO-8601 UTC string.
  updated_at VARCHAR(32) NOT NULL            -- Last modification time, ISO-8601 UTC string.
);
CREATE UNIQUE INDEX idx_organizations_external_id ON organizations (external_id);
CREATE UNIQUE INDEX idx_organizations_slug ON organizations (slug);

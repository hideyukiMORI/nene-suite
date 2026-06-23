-- TABLE: Operator-to-organization role assignments. organization_id is NULL for the platform superadmin; unique per (operator_id, organization_id).
CREATE TABLE memberships (
  id CHAR(26) NOT NULL PRIMARY KEY,          -- ULID primary key.
  operator_id CHAR(26) NOT NULL,             -- Operator this membership belongs to (logical ref operators.id; no cross-DB FK).
  organization_id CHAR(26) NULL,             -- Organization scope (logical ref organizations.id). NULL = platform-level superadmin.
  role VARCHAR(32) NOT NULL,                 -- Role: superadmin (platform, org NULL) | admin | member | viewer (org-scoped).
  created_at VARCHAR(32) NOT NULL,           -- Record creation time, ISO-8601 UTC string.
  updated_at VARCHAR(32) NOT NULL            -- Last modification time, ISO-8601 UTC string.
);
CREATE UNIQUE INDEX idx_memberships_operator_org ON memberships (operator_id, organization_id);
CREATE INDEX idx_memberships_organization ON memberships (organization_id);

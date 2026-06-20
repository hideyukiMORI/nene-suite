CREATE TABLE memberships (
  id CHAR(26) NOT NULL PRIMARY KEY,
  operator_id CHAR(26) NOT NULL,
  organization_id CHAR(26) NULL,
  role VARCHAR(32) NOT NULL,
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL
);
CREATE UNIQUE INDEX idx_memberships_operator_org ON memberships (operator_id, organization_id);
CREATE INDEX idx_memberships_organization ON memberships (organization_id);

CREATE TABLE organizations (
  id CHAR(26) NOT NULL PRIMARY KEY,
  external_id CHAR(26) NOT NULL,
  name VARCHAR(320) NOT NULL,
  slug VARCHAR(160) NOT NULL,
  status VARCHAR(32) NOT NULL,
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL
);
CREATE UNIQUE INDEX idx_organizations_external_id ON organizations (external_id);
CREATE UNIQUE INDEX idx_organizations_slug ON organizations (slug);

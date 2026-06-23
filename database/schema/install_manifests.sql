-- GROUP: Install
-- TABLE: Point-in-time install snapshots (JSON, no secrets) for audit alignment and operator export. content_hash is the SHA-256 of content_json.
CREATE TABLE install_manifests (
  id CHAR(26) NOT NULL PRIMARY KEY,          -- ULID primary key.
  suite_id CHAR(26) NOT NULL,                -- Suite installation id (logical ref install_sessions.suite_id).
  content_json TEXT NOT NULL,                -- Manifest body as JSON (suite_id, installed_at, app_versions, org_external_id, enabled_integrations, ...). No secrets.
  content_hash VARCHAR(64) NOT NULL,         -- SHA-256 hex of content_json for audit alignment and change detection.
  created_at VARCHAR(32) NOT NULL            -- Manifest creation time, ISO-8601 UTC string.
);
CREATE INDEX idx_install_manifests_suite_id ON install_manifests (suite_id);

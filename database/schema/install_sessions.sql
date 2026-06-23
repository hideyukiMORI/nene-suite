-- TABLE: Installer run state (Tier B). One row per install run: in_progress to completed or failed. Holds no secrets.
CREATE TABLE install_sessions (
  id CHAR(26) NOT NULL PRIMARY KEY,              -- ULID primary key.
  suite_id CHAR(26) NOT NULL,                    -- Suite installation id (matches NENE_SUITE_ID and manifest suite_id).
  status VARCHAR(32) NOT NULL,                   -- Installer workflow state: in_progress | completed | failed.
  tier VARCHAR(8) NOT NULL,                      -- Deployment tier: B (Docker/VPS, current) | A (shared hosting, planned).
  catalog_revision INTEGER NOT NULL,             -- App-catalog schema revision captured at install start.
  selected_apps_json TEXT NOT NULL,              -- JSON array of selected catalog ids (dependency-resolved).
  disclaimer_accepted INTEGER NOT NULL DEFAULT 0,-- Whether the operator accepted the installer disclaimer (0/1).
  disclaimer_accepted_at VARCHAR(32) NULL,       -- When the disclaimer was accepted, ISO-8601 UTC string. NULL until accepted.
  org_external_id CHAR(26) NULL,                 -- Federation UUID (ULID) chosen for the organization. NULL until set.
  org_display_name VARCHAR(500) NULL,            -- Operator-entered organization name. NULL until set.
  install_manifest_id CHAR(26) NULL,             -- Manifest produced on completion (logical ref install_manifests.id). NULL until completed.
  failure_code VARCHAR(64) NULL,                 -- Machine-readable error code when status=failed. NULL otherwise.
  created_at VARCHAR(32) NOT NULL,               -- Session creation time, ISO-8601 UTC string.
  updated_at VARCHAR(32) NOT NULL,               -- Last state-change time, ISO-8601 UTC string.
  completed_at VARCHAR(32) NULL                  -- Completion time, ISO-8601 UTC string. NULL until completed.
);
CREATE INDEX idx_install_sessions_suite_id ON install_sessions (suite_id);
CREATE INDEX idx_install_sessions_status ON install_sessions (status);

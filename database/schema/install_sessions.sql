CREATE TABLE install_sessions (
  id CHAR(26) NOT NULL PRIMARY KEY,
  suite_id CHAR(26) NOT NULL,
  status VARCHAR(32) NOT NULL,
  tier VARCHAR(8) NOT NULL,
  catalog_revision INTEGER NOT NULL,
  selected_apps_json TEXT NOT NULL,
  disclaimer_accepted INTEGER NOT NULL DEFAULT 0,
  disclaimer_accepted_at VARCHAR(32) NULL,
  org_external_id CHAR(26) NULL,
  org_display_name VARCHAR(500) NULL,
  install_manifest_id CHAR(26) NULL,
  failure_code VARCHAR(64) NULL,
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL,
  completed_at VARCHAR(32) NULL
);
CREATE INDEX idx_install_sessions_suite_id ON install_sessions (suite_id);
CREATE INDEX idx_install_sessions_status ON install_sessions (status);

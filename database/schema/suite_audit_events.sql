CREATE TABLE suite_audit_events (
  id CHAR(26) NOT NULL PRIMARY KEY,
  suite_id CHAR(26) NOT NULL,
  org_external_id CHAR(26) NULL,
  actor_user_id CHAR(26) NULL,
  actor_label VARCHAR(320) NULL,
  action VARCHAR(128) NOT NULL,
  entity_type VARCHAR(64) NOT NULL,
  entity_id VARCHAR(64) NOT NULL,
  before_json TEXT NULL,
  after_json TEXT NULL,
  created_at VARCHAR(32) NOT NULL,
  source VARCHAR(32) NOT NULL,
  request_id VARCHAR(128) NULL,
  install_session_id CHAR(26) NULL,
  metadata_json TEXT NULL
);
CREATE INDEX idx_suite_audit_events_suite_id ON suite_audit_events (suite_id);
CREATE INDEX idx_suite_audit_events_install_session_id ON suite_audit_events (install_session_id);
CREATE INDEX idx_suite_audit_events_action ON suite_audit_events (action);

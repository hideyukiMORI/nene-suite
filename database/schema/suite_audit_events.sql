-- TABLE: Append-only audit trail of mutating suite actions (ADR 0007). before_json/after_json are sanitized snapshots with secrets redacted.
CREATE TABLE suite_audit_events (
  id CHAR(26) NOT NULL PRIMARY KEY,          -- ULID primary key.
  suite_id CHAR(26) NOT NULL,                -- Suite installation id (matches NENE_SUITE_ID).
  org_external_id CHAR(26) NULL,             -- Federation UUID when known; NULL for pre-auth wizard events.
  actor_user_id CHAR(26) NULL,               -- Authenticated operator id (logical ref operators.id); NULL for pre-auth or system events.
  actor_label VARCHAR(320) NULL,             -- Human-readable actor label (e.g. operator email at disclaimer step). No secrets.
  action VARCHAR(128) NOT NULL,              -- Action as {entity}.{verb}, e.g. install_session.completed, membership.granted. See audit-trail.md.
  entity_type VARCHAR(64) NOT NULL,          -- Affected entity category, e.g. install_session | organization | membership | federation_signing_key. See audit-trail.md.
  entity_id VARCHAR(64) NOT NULL,            -- Stable id of the affected entity (ULID, suite_id, or other domain key).
  before_json TEXT NULL,                     -- Sanitized state before the change as JSON; NULL on create. Secrets redacted.
  after_json TEXT NULL,                      -- Sanitized state after the change as JSON; NULL when policy allows. Secrets redacted.
  created_at VARCHAR(32) NOT NULL,           -- Server-generated event time, ISO-8601 UTC string.
  source VARCHAR(32) NOT NULL,               -- Origin: installer_ui | installer_cli | apex_admin | system | api.
  request_id VARCHAR(128) NULL,              -- Correlation id from the X-Request-Id header when present. NULL otherwise.
  install_session_id CHAR(26) NULL,          -- Groups wizard steps of one install run (logical ref install_sessions.id). NULL otherwise.
  metadata_json TEXT NULL                    -- Event-specific extras as JSON (e.g. failure reason, dependency list). Sanitized.
);
CREATE INDEX idx_suite_audit_events_suite_id ON suite_audit_events (suite_id);
CREATE INDEX idx_suite_audit_events_install_session_id ON suite_audit_events (install_session_id);
CREATE INDEX idx_suite_audit_events_action ON suite_audit_events (action);

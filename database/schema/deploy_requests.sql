-- GROUP: Deploy
-- TABLE: Deploy-control seam queue (ADR 0019 OQ1, S2-1a / #361): one row per "recreate service X at image digest D" request handed to the host-side deploy agent. The suite writes pending rows and records the agent-reported terminal result; the compose pull + recreate itself runs on the host, never in the suite container. Disabled-degrade: no rows are ever created while the capability flag is off.
CREATE TABLE deploy_requests (
  id CHAR(26) NOT NULL PRIMARY KEY,          -- ULID primary key.
  service VARCHAR(100) NOT NULL,             -- Catalog app id (explicit allow-list; matches catalog/apps.json id).
  image_digest VARCHAR(96) NOT NULL,         -- Immutable image pin, sha256:<64 hex> (ADR 0019 OQ2 stage 1). Never a mutable tag.
  status VARCHAR(16) NOT NULL,               -- Lifecycle: pending | succeeded | failed. Terminal states are agent-reported.
  requested_by CHAR(26) NULL,                -- Requesting operator ULID. Requests are operator-initiated (superadmin surface).
  detail TEXT NULL,                          -- Agent-reported outcome detail (failure reason etc.). NULL until terminal.
  created_at VARCHAR(32) NOT NULL,           -- Request time, ISO-8601 UTC string.
  updated_at VARCHAR(32) NOT NULL,           -- Last transition time, ISO-8601 UTC string.
  completed_at VARCHAR(32) NULL              -- Terminal report time, ISO-8601 UTC string. NULL while pending.
);
CREATE INDEX idx_deploy_requests_status_created_at ON deploy_requests (status, created_at);

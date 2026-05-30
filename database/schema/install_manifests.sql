CREATE TABLE install_manifests (
  id CHAR(26) NOT NULL PRIMARY KEY,
  suite_id CHAR(26) NOT NULL,
  content_json TEXT NOT NULL,
  content_hash VARCHAR(64) NOT NULL,
  created_at VARCHAR(32) NOT NULL
);
CREATE INDEX idx_install_manifests_suite_id ON install_manifests (suite_id);

CREATE TABLE revoked_tokens (
  jti CHAR(26) NOT NULL PRIMARY KEY,
  token_type VARCHAR(32) NOT NULL,
  expires_at BIGINT NOT NULL,
  revoked_at VARCHAR(32) NOT NULL,
  reason VARCHAR(64) NOT NULL
);
CREATE INDEX idx_revoked_tokens_expires ON revoked_tokens (expires_at);

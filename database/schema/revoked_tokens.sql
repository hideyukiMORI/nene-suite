-- GROUP: Auth
-- TABLE: Logout/revocation denylist of JWT jti values. Ephemeral; rows reclaimed once expires_at passes.
CREATE TABLE revoked_tokens (
  jti CHAR(26) NOT NULL PRIMARY KEY,         -- JWT jti claim and primary key (ULID). Revocation is idempotent.
  token_type VARCHAR(32) NOT NULL,           -- Revoked token type: apex_session (current). Extensible.
  expires_at BIGINT NOT NULL,                -- Token exp time, epoch seconds (BIGINT). Used for GC.
  revoked_at VARCHAR(32) NOT NULL,           -- When revocation was recorded, ISO-8601 UTC string.
  reason VARCHAR(64) NOT NULL                -- Revocation reason, e.g. logout. Extensible.
);
CREATE INDEX idx_revoked_tokens_expires ON revoked_tokens (expires_at);

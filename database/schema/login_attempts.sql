CREATE TABLE login_attempts (
  attempt_key VARCHAR(160) NOT NULL PRIMARY KEY,
  window_started_at BIGINT NOT NULL,
  attempt_count INT NOT NULL
);
CREATE INDEX idx_login_attempts_window ON login_attempts (window_started_at);

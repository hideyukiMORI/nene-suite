-- TABLE: Fixed-window login rate-limit counters. One row per attempt_key; ephemeral, reclaimed by opportunistic GC.
CREATE TABLE login_attempts (
  attempt_key VARCHAR(160) NOT NULL PRIMARY KEY, -- Rate-limit key and primary key, e.g. ip:192.0.2.1.
  window_started_at BIGINT NOT NULL,             -- Start of the current fixed window, epoch seconds (BIGINT).
  attempt_count INT NOT NULL                     -- Failed-attempt count within the current window.
);
CREATE INDEX idx_login_attempts_window ON login_attempts (window_started_at);

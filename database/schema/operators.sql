-- TABLE: Apex login shell accounts (operators). password_hash is a bcrypt/argon hash, never plaintext.
-- The -- annotations mirror the MySQL COMMENTs applied by the Phinx migration
-- add_table_and_column_comments; SQLite (used by tests) ignores them.
CREATE TABLE operators (
  id CHAR(26) NOT NULL PRIMARY KEY,          -- ULID primary key.
  email VARCHAR(320) NOT NULL,               -- Login email address. Unique (idx_operators_email).
  password_hash VARCHAR(255) NOT NULL,       -- Bcrypt/argon password hash. Never plaintext; never logged, audited, or exported.
  display_name VARCHAR(320) NULL,            -- Optional human-readable operator name.
  created_at VARCHAR(32) NOT NULL,           -- Record creation time, ISO-8601 UTC string.
  updated_at VARCHAR(32) NOT NULL            -- Last modification time, ISO-8601 UTC string.
);
CREATE UNIQUE INDEX idx_operators_email ON operators (email);

CREATE TABLE operators (
  id CHAR(26) NOT NULL PRIMARY KEY,
  email VARCHAR(320) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  display_name VARCHAR(320) NULL,
  created_at VARCHAR(32) NOT NULL,
  updated_at VARCHAR(32) NOT NULL
);
CREATE UNIQUE INDEX idx_operators_email ON operators (email);

CREATE TABLE federation_signing_keys (
  id CHAR(26) NOT NULL PRIMARY KEY,
  kid VARCHAR(64) NOT NULL,
  alg VARCHAR(16) NOT NULL,
  public_jwk TEXT NOT NULL,
  status VARCHAR(16) NOT NULL,
  created_at VARCHAR(32) NOT NULL,
  activated_at VARCHAR(32) NULL,
  retired_at VARCHAR(32) NULL
);
CREATE UNIQUE INDEX idx_federation_signing_keys_kid ON federation_signing_keys (kid);
CREATE INDEX idx_federation_signing_keys_status ON federation_signing_keys (status);

-- GROUP: Federation
-- TABLE: Federation IdP signing keys: public JWK only (private key never stored). Exactly one row is active; status drives JWKS publication.
CREATE TABLE federation_signing_keys (
  id CHAR(26) NOT NULL PRIMARY KEY,          -- ULID primary key.
  kid VARCHAR(64) NOT NULL,                  -- Key id: RFC 7638 JWK thumbprint. Unique.
  alg VARCHAR(16) NOT NULL,                  -- Signing algorithm: ES256 (current). Extensible.
  public_jwk TEXT NOT NULL,                  -- Public key as a JWK JSON object. Never contains private key material.
  status VARCHAR(16) NOT NULL,               -- Lifecycle: active | retiring | retired | revoked. Drives JWKS publication.
  created_at VARCHAR(32) NOT NULL,           -- Key generation time, ISO-8601 UTC string.
  activated_at VARCHAR(32) NULL,             -- When the key became active, ISO-8601 UTC string. NULL until activated.
  retired_at VARCHAR(32) NULL                -- When the key was retired/revoked, ISO-8601 UTC string. NULL otherwise.
);
CREATE UNIQUE INDEX idx_federation_signing_keys_kid ON federation_signing_keys (kid);
CREATE INDEX idx_federation_signing_keys_status ON federation_signing_keys (status);

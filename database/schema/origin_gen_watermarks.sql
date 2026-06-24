-- GROUP: Origin
-- TABLE: Profiled-TUF anti-rollback generation watermark for the Origin read model (ADR 0017 §5). One row per product slug, advanced monotonically; supplies persisted_gen to the consumer verifier so a replayed older current fails closed.
CREATE TABLE origin_gen_watermarks (
  product VARCHAR(100) NOT NULL PRIMARY KEY,  -- Catalog product slug. Product-scoped, cross-channel (one watermark per product).
  gen BIGINT NOT NULL,                        -- Highest accepted profiled-TUF generation; advanced monotonically (never regresses).
  updated_at VARCHAR(32) NOT NULL             -- Last advance time, ISO-8601 UTC string.
);

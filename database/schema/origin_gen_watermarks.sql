-- GROUP: Origin
-- TABLE: Profiled-TUF anti-rollback generation watermark for the Origin read model (ADR 0017 §5). One row per tree coordinate, advanced monotonically; supplies persisted_gen to the consumer verifier so a replayed older current fails closed.
CREATE TABLE origin_gen_watermarks (
  coordinate VARCHAR(255) NOT NULL PRIMARY KEY,  -- Tree coordinate the watermark belongs to: update:{product} | feed:{product}/{audience}/{locale} | entitlement:{product}/{audience}. Origin numbers gen independently per coordinate, so one row per product would pin unrelated trees above their own gen and reject them as rollback.
  gen BIGINT NOT NULL,                           -- Highest accepted profiled-TUF generation at this coordinate; advanced monotonically (never regresses).
  updated_at VARCHAR(32) NOT NULL                -- Last advance time, ISO-8601 UTC string.
);

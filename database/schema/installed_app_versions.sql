-- GROUP: Origin
-- TABLE: Last-known installed version per suite-managed product, learned from the sibling /health probe (#255, epic #251 prerequisite). Supplies the installed version to the Origin update aggregator so a verified latest version can be diffed (unknown -> up_to_date / update_available / forced) instead of surfaced latest-only. Last-write-wins; absence of a row means the version is unknown.
CREATE TABLE installed_app_versions (
  catalog_id VARCHAR(100) NOT NULL PRIMARY KEY,  -- Catalog product slug (matches catalog/apps.json id).
  version VARCHAR(64) NOT NULL,                   -- Installed semver as reported by the sibling /health version field.
  checked_at VARCHAR(32) NOT NULL                 -- Last successful probe time, ISO-8601 UTC string.
);

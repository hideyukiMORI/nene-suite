<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The key a profiled-TUF anti-rollback watermark is stored under (ADR 0017 §5).
 *
 * Origin numbers `gen` **independently per tree coordinate**, so a watermark is only comparable to
 * generations from the same coordinate. Sharing one key across coordinates is not a
 * simplification — it fails closed: reading a tree whose counter runs ahead pins the watermark
 * above every other tree's current `gen`, and those trees then reject as `rollback`
 * (suite #424 — an `update` at gen 42 made the same product's `feed` at gen 7 unreadable).
 *
 * The coordinates match Origin's delegation keys (origin #608/#615):
 *
 * - **update** — `{product}`, cross-channel: one counter spans `stable` / `beta` for a product.
 * - **feed** — `{product}/{audience}/{locale}`: the `ja` and `en` variants of one feed are
 *   separate counters, so the missing-locale fallback reads the `en` coordinate, not `ja`'s.
 * - **entitlement** — `{product}/{audience}`.
 *
 * The rendered key carries the tree kind as a prefix, which is what makes a cross-tree collision
 * structurally impossible rather than merely unlikely. Components come from the catalog and are
 * already interpolated into object paths (`v1/feeds/{product}/{audience}/{locale}/current`), so
 * they are slug-shaped by construction; no component may contain `/` or `:`.
 */
final readonly class OriginGenWatermarkCoordinate
{
    private function __construct(
        public OriginTreeKind $tree,
        public string $key,
    ) {
    }

    /** Cross-channel: `stable` and `beta` share one counter for a product (origin #608(a)). */
    public static function forUpdate(string $product): self
    {
        return new self(OriginTreeKind::Update, sprintf('update:%s', $product));
    }

    /** Per locale: the `en` fallback target is a different counter than the requested locale. */
    public static function forFeed(string $product, string $audience, string $locale): self
    {
        return new self(OriginTreeKind::Feed, sprintf('feed:%s/%s/%s', $product, $audience, $locale));
    }

    public static function forEntitlement(string $product, string $audience): self
    {
        return new self(OriginTreeKind::Entitlement, sprintf('entitlement:%s/%s', $product, $audience));
    }
}

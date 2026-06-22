<?php

declare(strict_types=1);

namespace NeNeSuite\Http;

/**
 * Product edition (ADR 0015). `Oss` is the self-hosted, single-organization default; `Hosted`
 * is the vendor-operated multi-organization service ("NeNe Cloud Free").
 *
 * The edition is a THIRD, orthogonal axis — deliberately not folded into `NENE_SUITE_MODE`
 * (joined-to-a-hub) or `NENE_SUITE_ALLOW_DEV_SECRET` (dev-secret opt-in). It gates the Phase B
 * federation/IdP key plane: hosted-only services and routes are simply never constructed when the
 * edition is `Oss`, so a self-hosted install holds no asymmetric key material and its behavior is
 * unchanged (ADR 0015 §8).
 *
 * Fail-closed: anything other than the exact string `hosted` resolves to `Oss`, so a typo or a
 * truthy-but-wrong value can never accidentally expose the hosted surface.
 */
enum Edition: string
{
    case Oss = 'oss';
    case Hosted = 'hosted';

    public static function fromEnv(?string $raw): self
    {
        return $raw === 'hosted' ? self::Hosted : self::Oss;
    }

    public function isHosted(): bool
    {
        return $this === self::Hosted;
    }

    public function isOss(): bool
    {
        return $this === self::Oss;
    }
}

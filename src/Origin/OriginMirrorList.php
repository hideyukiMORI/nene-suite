<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The ordered list of Origin read-path base URLs (`nene-origin/docs/spec/mirrors.md`). Mirrors are
 * **untrusted transports**: the list is an availability preference only — signature verification is
 * the sole trust decision, so a hostile mirror can deny service but never make the client accept
 * tampered bytes.
 *
 * Distribution is **out-of-band**: {@see embeddedDefault()} ships inside the client build (there is
 * no signed `mirrors.json` on the read path at launch). Setting `NENE_ORIGIN_URL` yields
 * {@see exclusive()} — exactly that one base, with **no** fallback to the defaults (the dev /
 * staging / self-host escape hatch, and the operator-side bridge when the embedded list goes stale).
 */
final readonly class OriginMirrorList
{
    /**
     * The production list, in preference order (mirrors.md §2 is the single registry; changing it
     * is a contract change on the Origin side that ships new client defaults here).
     */
    private const array EMBEDDED_DEFAULT = [
        'https://nene-origin.dev',      // HETEML (primary)
        'https://m2.nene-origin.dev',   // ConoHa VPS (secondary)
    ];

    /**
     * @param list<string> $baseUrls ordered, without a trailing slash; empty = Origin disabled
     */
    public function __construct(
        public array $baseUrls,
    ) {
    }

    public static function embeddedDefault(): self
    {
        return new self(self::EMBEDDED_DEFAULT);
    }

    /** The `NENE_ORIGIN_URL` override: this base only, never falling back to the embedded list. */
    public static function exclusive(string $baseUrl): self
    {
        $normalized = rtrim(trim($baseUrl), '/');

        return new self($normalized === '' ? [] : [$normalized]);
    }

    public static function none(): self
    {
        return new self([]);
    }

    public function isEmpty(): bool
    {
        return $this->baseUrls === [];
    }
}

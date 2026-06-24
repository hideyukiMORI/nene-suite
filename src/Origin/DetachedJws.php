<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use JsonException;
use SodiumException;

/**
 * A parsed detached JWS sidecar (RFC 7515 + RFC 7797 unencoded payload), served
 * compact-detached as `BASE64URL(protected).` + `.BASE64URL(signature)` (empty
 * payload). The protected header MUST be `{alg, kid, b64:false, crit:["b64"]}`.
 * The `protectedB64` segment is kept verbatim — it is part of the signing input
 * (`ASCII(BASE64URL(protected) + ".") ‖ body`), so it must not be re-encoded.
 */
final readonly class DetachedJws
{
    public function __construct(
        public string $protectedB64,
        public string $alg,
        public string $kid,
        public string $signature,
    ) {
    }

    /**
     * @throws OriginVerificationException when the sidecar is not a well-formed
     *                                     RFC 7797 detached JWS
     */
    public static function parse(string $compact): self
    {
        $parts = explode('.', $compact);

        if (count($parts) !== 3 || $parts[1] !== '') {
            throw OriginVerificationException::malformedJws('expected compact-detached form protected..signature');
        }

        [$protectedB64, , $signatureB64] = $parts;

        try {
            $headerJson = Base64Url::decode($protectedB64);
            $signature = Base64Url::decode($signatureB64);
        } catch (SodiumException) {
            throw OriginVerificationException::malformedJws('invalid base64url segment');
        }

        try {
            /** @var mixed $header */
            $header = json_decode($headerJson, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw OriginVerificationException::malformedJws('protected header is not valid JSON');
        }

        if (!is_array($header)) {
            throw OriginVerificationException::malformedJws('protected header is not an object');
        }

        if (($header['b64'] ?? null) !== false) {
            throw OriginVerificationException::unencodedPayloadRequired();
        }

        $crit = $header['crit'] ?? null;

        if (!is_array($crit) || !in_array('b64', $crit, true)) {
            throw OriginVerificationException::malformedJws('crit must include b64');
        }

        $alg = $header['alg'] ?? null;
        $kid = $header['kid'] ?? null;

        if (!is_string($alg) || $alg === '') {
            throw OriginVerificationException::malformedJws('missing or invalid alg');
        }

        if (!is_string($kid) || $kid === '') {
            throw OriginVerificationException::malformedJws('missing or invalid kid');
        }

        return new self($protectedB64, $alg, $kid, $signature);
    }
}

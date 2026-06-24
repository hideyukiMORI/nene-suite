<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use SodiumException;

/**
 * Strict base64url (no padding) used by JWK members and the JWS segments.
 * Backed by libsodium so malformed input throws rather than silently truncating.
 */
final class Base64Url
{
    /**
     * @throws SodiumException when $value is not valid unpadded base64url
     */
    public static function decode(string $value): string
    {
        return sodium_base642bin($value, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING, '');
    }

    public static function encode(string $bytes): string
    {
        return sodium_bin2base64($bytes, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }
}

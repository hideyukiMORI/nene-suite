<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use RuntimeException;

/**
 * Generates an ES256 (EC P-256) federation key pair and derives its public JWK + RFC 7638 `kid`
 * (milestone B1.5). The private key is returned for the operator to store out of band; the suite
 * keeps only the public JWK.
 */
final class FederationKeyGenerator
{
    public const ALG = 'ES256';

    /** P-256 field size in bytes; EC JWK coordinates are fixed-length, left zero-padded (RFC 7518 §6.2.1.2). */
    private const COORDINATE_BYTES = 32;

    public function generate(): GeneratedFederationKey
    {
        $resource = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);

        if ($resource === false) {
            throw new RuntimeException('EC key generation failed — ext-openssl with P-256 (prime256v1) is required.');
        }

        $privatePem = '';
        openssl_pkey_export($resource, $privatePem);

        $details = openssl_pkey_get_details($resource);

        if (!is_array($details) || !is_array($details['ec'] ?? null) || !isset($details['ec']['x'], $details['ec']['y'])) {
            throw new RuntimeException('Could not read EC public coordinates from the generated key.');
        }

        // openssl returns coordinates as minimal big-endian integers, so a leading-zero byte is
        // dropped (~1/256 per coordinate). JWK requires the full field size, left zero-padded —
        // otherwise the kid (RFC 7638 thumbprint over the coordinates) won't match a compliant
        // verifier's, intermittently breaking federation.
        $x = $this->base64Url($this->padCoordinate((string) $details['ec']['x']));
        $y = $this->base64Url($this->padCoordinate((string) $details['ec']['y']));
        $kid = JwkThumbprint::computeEc('P-256', $x, $y);

        $publicJwk = [
            'kty' => 'EC',
            'crv' => 'P-256',
            'x' => $x,
            'y' => $y,
            'use' => 'sig',
            'alg' => self::ALG,
            'kid' => $kid,
        ];

        return new GeneratedFederationKey(
            privateKeyPem: $privatePem,
            publicJwk: (string) json_encode($publicJwk),
            kid: $kid,
            alg: self::ALG,
        );
    }

    private function padCoordinate(string $coordinate): string
    {
        return str_pad($coordinate, self::COORDINATE_BYTES, "\x00", STR_PAD_LEFT);
    }

    private function base64Url(string $binary): string
    {
        return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
    }
}

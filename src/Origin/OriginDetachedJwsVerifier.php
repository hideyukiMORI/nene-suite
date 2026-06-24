<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use SodiumException;

/**
 * Verifies a detached `.jws` (compact or JWS JSON General) over the literal served body bytes,
 * against a role's delegated keys, requiring a threshold of distinct valid signers
 * (verification-order.md §2/§3). Per signature it enforces, in order: the `alg` allowlist (fail
 * closed on `"none"`/symmetric for the whole object), `b64:false` + `crit:["b64"]`, the `kid` being
 * in the role, kid-valid-at-iat, then the Ed25519 check over `BASE64URL(protected) || '.' || body`.
 * A faithful port of Origin's reference verifier — no generic JWS/TUF parser.
 */
final class OriginDetachedJwsVerifier
{
    /**
     * @param array<string, OriginPublicKeyMaterial> $keysByKid available key material, by kid
     * @param list<string>                           $roleKeyids kids authorised for this role
     */
    public static function verify(string $jws, string $payload, array $keysByKid, array $roleKeyids, int $threshold): OriginJwsVerification
    {
        // A threshold below 1 would accept an unsigned object — fail closed against a malformed
        // or forged role/anchor (e.g. a missing/zero threshold).
        if ($threshold < 1) {
            return new OriginJwsVerification(false, OriginVerificationReason::RoleThresholdNotMet, []);
        }

        $entries = self::parse($jws);
        if ($entries === null) {
            return new OriginJwsVerification(false, OriginVerificationReason::MalformedObject, []);
        }

        $inRole = array_fill_keys($roleKeyids, true);
        $validKids = [];
        $sawInRoleKid = false;
        $sawKidNotValidAtIat = false;
        $sawSignatureInvalid = false;

        foreach ($entries as $entry) {
            $header = self::decodeHeader($entry['protected']);
            if ($header === null) {
                $sawSignatureInvalid = true;
                continue;
            }
            // Fail closed for the whole object on a disallowed alg — never let "none" through.
            if (!OriginAlgorithmAllowlist::isAllowed($header['alg'] ?? null)) {
                return new OriginJwsVerification(false, OriginVerificationReason::AlgNotAllowed, []);
            }
            // RFC 7797 §6: the unencoded-payload signature MUST declare b64:false AND carry "b64"
            // in the integrity-protected `crit` header.
            $crit = $header['crit'] ?? null;
            if (($header['b64'] ?? true) !== false || !is_array($crit) || !in_array('b64', $crit, true)) {
                $sawSignatureInvalid = true;
                continue;
            }
            $kid = $header['kid'] ?? null;
            if (!is_string($kid) || !isset($inRole[$kid])) {
                continue; // not a signer this role recognises
            }
            $material = $keysByKid[$kid] ?? null;
            if ($material === null) {
                continue; // declared in the role but no key material published
            }
            $sawInRoleKid = true;
            $iat = $header['iat'] ?? null;
            if (!is_int($iat)) {
                $sawSignatureInvalid = true;
                continue;
            }
            if (!$material->validAt($iat)) {
                $sawKidNotValidAtIat = true; // kid retired/revoked before it signed
                continue;
            }
            $signature = self::tryDecode($entry['signature']);
            if ($signature === null) {
                $sawSignatureInvalid = true;
                continue;
            }
            $signingInput = $entry['protected'] . '.' . $payload;
            if (!OriginEd25519::verify($signature, $signingInput, $material->publicKey)) {
                $sawSignatureInvalid = true;
                continue;
            }
            $validKids[$kid] = true;
        }

        if (count($validKids) >= $threshold) {
            return new OriginJwsVerification(true, null, array_keys($validKids));
        }

        return new OriginJwsVerification(false, self::failureReason(
            count($validKids) > 0,
            $sawKidNotValidAtIat,
            $sawSignatureInvalid,
            $sawInRoleKid,
        ), array_keys($validKids));
    }

    private static function failureReason(bool $someValid, bool $notValidAtIat, bool $signatureInvalid, bool $inRoleKid): OriginVerificationReason
    {
        if ($someValid) {
            return OriginVerificationReason::RoleThresholdNotMet; // valid signatures, just not enough
        }
        if ($notValidAtIat) {
            return OriginVerificationReason::KidNotValidAtIat;
        }
        if ($signatureInvalid) {
            return OriginVerificationReason::SignatureInvalid;
        }
        if (!$inRoleKid) {
            return OriginVerificationReason::KidUnknown;
        }

        return OriginVerificationReason::RoleThresholdNotMet;
    }

    /**
     * @return list<array{protected: string, signature: string}>|null
     */
    private static function parse(string $jws): ?array
    {
        $trimmed = ltrim($jws);

        // JWS JSON General Serialization (M-of-N, e.g. root): { "signatures": [ {protected, signature} ] }.
        if (str_starts_with($trimmed, '{')) {
            $object = json_decode($jws, true);
            if (!is_array($object) || !isset($object['signatures']) || !is_array($object['signatures'])) {
                return null;
            }
            $entries = [];
            foreach ($object['signatures'] as $signature) {
                if (!is_array($signature)
                    || !isset($signature['protected'], $signature['signature'])
                    || !is_string($signature['protected'])
                    || !is_string($signature['signature'])
                ) {
                    return null;
                }
                $entries[] = ['protected' => $signature['protected'], 'signature' => $signature['signature']];
            }

            return $entries;
        }

        // Compact detached: BASE64URL(protected) '..' BASE64URL(signature) — the payload is omitted.
        $parts = explode('.', rtrim($jws, "\r\n"));
        if (count($parts) !== 3 || $parts[1] !== '') {
            return null;
        }

        return [['protected' => $parts[0], 'signature' => $parts[2]]];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function decodeHeader(string $protectedB64): ?array
    {
        $json = self::tryDecode($protectedB64);
        if ($json === null) {
            return null;
        }
        $header = json_decode($json, true);

        return is_array($header) ? $header : null;
    }

    private static function tryDecode(string $base64Url): ?string
    {
        try {
            return Base64Url::decode($base64Url);
        } catch (SodiumException) {
            return null;
        }
    }
}

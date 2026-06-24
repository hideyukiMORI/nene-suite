<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use RuntimeException;

/**
 * A signed Origin object failed verification. Carries a {@see OriginVerificationFailure}
 * so callers (and the conformance corpus) can assert the exact reason, never trusting
 * the object's fields when this is thrown.
 */
final class OriginVerificationException extends RuntimeException
{
    public function __construct(
        public readonly OriginVerificationFailure $failure,
        string $detail,
    ) {
        parent::__construct(sprintf('[%s] %s', $failure->value, $detail));
    }

    public static function signatureInvalid(string $detail = 'signature did not verify'): self
    {
        return new self(OriginVerificationFailure::SignatureInvalid, $detail);
    }

    public static function algorithmDisallowed(string $alg): self
    {
        return new self(OriginVerificationFailure::AlgorithmDisallowed, sprintf('algorithm not allowed: %s', $alg));
    }

    public static function algorithmNotImplemented(string $alg): self
    {
        return new self(
            OriginVerificationFailure::AlgorithmNotImplemented,
            sprintf('algorithm allowed but consumer support pending: %s', $alg),
        );
    }

    public static function malformedJws(string $detail): self
    {
        return new self(OriginVerificationFailure::MalformedJws, $detail);
    }

    public static function unencodedPayloadRequired(): self
    {
        return new self(
            OriginVerificationFailure::UnencodedPayloadRequired,
            'protected header must set b64:false with crit:["b64"] (RFC 7797)',
        );
    }

    public static function keyAlgorithmMismatch(string $detail = 'key alg does not match signature alg'): self
    {
        return new self(OriginVerificationFailure::KeyAlgorithmMismatch, $detail);
    }

    public static function malformedKey(string $detail): self
    {
        return new self(OriginVerificationFailure::MalformedKey, $detail);
    }
}

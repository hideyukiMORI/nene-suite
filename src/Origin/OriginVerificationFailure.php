<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * Why a signed Origin object failed verification. Stable string values map onto
 * the negative conformance corpus (O1 CI parity) and any future Problem Detail.
 */
enum OriginVerificationFailure: string
{
    case SignatureInvalid = 'signature_invalid';
    case AlgorithmDisallowed = 'algorithm_disallowed';
    case AlgorithmNotImplemented = 'algorithm_not_implemented';
    case MalformedJws = 'malformed_jws';
    case UnencodedPayloadRequired = 'unencoded_payload_required';
    case KeyAlgorithmMismatch = 'key_algorithm_mismatch';
    case MalformedKey = 'malformed_key';
}

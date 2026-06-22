<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

/**
 * Lifecycle of a federation signing key (ADR 0012 / milestone B1). Exactly one key is `Active`
 * (the one assertions are signed with). On rotation (B1.8) the prior active becomes `Retiring`
 * (still published in JWKS so in-flight assertions verify) then `Retired`. `Revoked` is an
 * emergency state that drops the key from JWKS immediately.
 */
enum FederationSigningKeyStatus: string
{
    case Active = 'active';
    case Retiring = 'retiring';
    case Retired = 'retired';
    case Revoked = 'revoked';
}

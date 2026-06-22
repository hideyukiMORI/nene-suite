<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface RevokeFederationSigningKeyUseCaseInterface
{
    /**
     * Revokes the key with the given kid. Returns true if it was revoked now, false if it was
     * already revoked (idempotent no-op).
     *
     * @throws FederationSigningKeyNotFoundException when no key has that kid
     */
    public function execute(RevokeFederationSigningKeyInput $input): bool;
}

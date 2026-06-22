<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Throwable;

/**
 * Hosted-edition boot preflight for the federation signing key (milestone B1.7). Fails closed when:
 *
 * - the private key is missing (the hosted IdP cannot sign assertions), or
 * - the private key is not loadable, or
 * - there is no active signing key row, or
 * - the private key's `kid` does not match the active row's `kid` — which would make the issuer sign
 *   with a `kid` that the published JWKS does not advertise, silently breaking all SSO.
 *
 * OSS skips this entirely (the entrypoint runs the check only in the hosted edition).
 */
final readonly class FederationKeyPreflight
{
    public function __construct(
        private FederationSigningKeyRepositoryInterface $keys,
        private FederationKeyGenerator $generator,
    ) {
    }

    /**
     * @throws FederationKeyPreflightException
     */
    public function verify(string $privateKeyPem): void
    {
        if (trim($privateKeyPem) === '') {
            throw new FederationKeyPreflightException(
                'NENE_SUITE_FEDERATION_PRIVATE_KEY (or _FILE) is required in the hosted edition.',
            );
        }

        try {
            $kid = $this->generator->kidForPrivateKey($privateKeyPem);
        } catch (Throwable $exception) {
            throw new FederationKeyPreflightException(
                'NENE_SUITE_FEDERATION_PRIVATE_KEY is not a loadable EC private key.',
                0,
                $exception,
            );
        }

        $active = $this->keys->findActive();

        if ($active === null) {
            throw new FederationKeyPreflightException(
                'No active federation signing key — run ops/keys/generate-federation-key.php and set NENE_SUITE_FEDERATION_PRIVATE_KEY.',
            );
        }

        if (!hash_equals($active->kid, $kid)) {
            throw new FederationKeyPreflightException(sprintf(
                'Federation private key kid "%s" does not match the active signing key "%s"; the JWKS would not advertise the signing kid (SSO outage).',
                $kid,
                $active->kid,
            ));
        }
    }
}

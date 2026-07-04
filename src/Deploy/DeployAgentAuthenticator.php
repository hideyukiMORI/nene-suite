<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Default-deny gate for the machine deploy seam (`/api/v1/machine/deploy/*`). Order:
 * capability flag (409 — the surface does not exist while disabled) → constant-time key
 * compare (401). The agent key pairs exactly one host-side agent with this suite
 * (ADR 0019 OQ1); it is never an operator credential and grants nothing else.
 */
final readonly class DeployAgentAuthenticator
{
    public const HEADER = 'X-NENE-SUITE-DEPLOY-KEY';

    public function __construct(
        private DeployAgentConfig $config,
    ) {
    }

    /**
     * @throws DeployCapabilityDisabledException while the capability flag is off (409)
     * @throws DeployAgentUnauthorizedException on a missing or mismatched key (401)
     */
    public function ensure(ServerRequestInterface $request): void
    {
        if (!$this->config->enabled) {
            throw new DeployCapabilityDisabledException();
        }

        $provided = $request->getHeaderLine(self::HEADER);

        if ($provided === '' || !hash_equals($this->config->agentKey, $provided)) {
            throw new DeployAgentUnauthorizedException();
        }
    }
}

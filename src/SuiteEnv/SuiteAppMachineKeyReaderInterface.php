<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

interface SuiteAppMachineKeyReaderInterface
{
    /**
     * The machine API key the suite presents to an installed sibling's auth-gated machine
     * endpoints (sent as `X-NENE2-API-Key`), or null when not configured. The value equals the
     * sibling's own `NENE2_MACHINE_API_KEY`.
     */
    public function machineApiKey(string $catalogId): ?string;
}

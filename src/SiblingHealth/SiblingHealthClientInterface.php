<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

/**
 * Outbound probe of a suite-managed sibling's auth-gated `/machine/health` (NENE2 >= v1.5.330).
 * Returns the sibling's reported installed version (the `version` field, a semver string) or null
 * when the machine credential is missing, `/machine/health` is unreachable, answers non-200,
 * returns a non-JSON body, or omits a usable `version` (the sibling has not injected its app
 * version yet). Best-effort and non-throwing: a down / version-less / un-credentialled sibling
 * degrades to null (the update signal stays `unknown`); it never aborts the Origin updates read.
 */
interface SiblingHealthClientInterface
{
    public function fetchVersion(string $publicUrl, ?string $machineApiKey): ?string;
}

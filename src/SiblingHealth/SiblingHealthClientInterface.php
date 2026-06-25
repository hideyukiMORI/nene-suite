<?php

declare(strict_types=1);

namespace NeNeSuite\SiblingHealth;

/**
 * Outbound probe of a suite-managed sibling's `/health`. Returns the sibling's reported installed
 * version (a semver string) or null when `/health` is unreachable, answers non-200, returns a
 * non-JSON body, or omits a usable `version` field. Best-effort and non-throwing: a down or
 * version-less sibling degrades to null (the update signal stays `unknown`); it never aborts the
 * Origin updates read.
 */
interface SiblingHealthClientInterface
{
    public function fetchVersion(string $publicUrl): ?string;
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

final readonly class CreateOrganizationInput
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $requestId = null,
        // Optional seed for the federation key. A valid ULID is canonicalised and used as
        // the org's external_id (so a backfill can reconcile with NENE_SUITE_ORG_EXTERNAL_ID);
        // null or malformed → a fresh ULID is minted. Existing callers pass nothing → mint.
        public ?string $externalId = null,
    ) {
    }
}

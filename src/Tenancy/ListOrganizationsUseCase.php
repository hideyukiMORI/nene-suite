<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * Lists every organization in the Suite registry (oldest first). Read-only — no transaction,
 * no audit. The superadmin platform console (milestone A7) consumes it.
 */
final readonly class ListOrganizationsUseCase implements ListOrganizationsUseCaseInterface
{
    public function __construct(
        private OrganizationRepositoryInterface $organizations,
    ) {
    }

    public function execute(): ListOrganizationsOutput
    {
        return new ListOrganizationsOutput($this->organizations->all());
    }
}

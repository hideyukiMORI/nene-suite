<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use NeNeSuite\Auth\OperatorRepositoryInterface;
use NeNeSuite\InstallManifest\InstallManifestRepositoryInterface;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\Tenancy\CreateOrganizationInput;
use NeNeSuite\Tenancy\CreateOrganizationUseCaseInterface;
use NeNeSuite\Tenancy\GrantMembershipInput;
use NeNeSuite\Tenancy\GrantMembershipUseCaseInterface;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\Organization;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;
use NeNeSuite\Tenancy\Role;

/**
 * Backfills tenancy onto an install that predates A4 (operators exist, but no organization
 * or memberships). Runs idempotently on every server start (entrypoint, after migrate —
 * ADR 0014) so a `docker compose up -d` upgrade gives existing operators a membership before
 * A6 makes the session JWT/authenticator read one (the lockout firewall).
 *
 * Policy (maintainer-confirmed): every operator gets a default-org `Admin` membership
 * (pre-tenancy operators were full apex admins — ADR 0015 §8 no-regress); the oldest operator
 * (min `created_at`, tiebreak `id`) ALSO gets a platform `Superadmin`, so the superadmin
 * console (A7) is reachable. A fresh DB (no operators) is a no-op — `install.php`/the apex
 * wizard own first-install bootstrap, not the serving backfill. Reuses the A2/A3 use cases
 * (audit `source=system`); each guarded grant makes re-runs a true no-op.
 */
final readonly class BackfillTenancyUseCase implements BackfillTenancyUseCaseInterface
{
    private const DEFAULT_ORG_NAME = 'Default Organization';

    public function __construct(
        private OperatorRepositoryInterface $operators,
        private OrganizationRepositoryInterface $organizations,
        private MembershipRepositoryInterface $memberships,
        private CreateOrganizationUseCaseInterface $createOrganization,
        private GrantMembershipUseCaseInterface $grantMembership,
        private InstallSessionRepositoryInterface $sessions,
        private InstallManifestRepositoryInterface $manifests,
        private string $suiteId,
        private string $orgExternalId,
    ) {
    }

    public function execute(BackfillTenancyInput $input): BackfillTenancyOutput
    {
        $operators = $this->operators->all();

        if ($operators === []) {
            return new BackfillTenancyOutput(null, 0, true);
        }

        $organization = $this->resolveDefaultOrganization($input->requestId);

        $backfilled = 0;

        foreach ($operators as $index => $operator) {
            // operators are oldest-first; operators[0] is the platform superadmin.
            if ($index === 0 && $this->memberships->findByOperatorAndOrganization($operator->id, null) === null) {
                $this->grantMembership->execute(
                    new GrantMembershipInput($operator->id, Role::Superadmin, null, $input->requestId),
                );
            }

            if ($this->memberships->findByOperatorAndOrganization($operator->id, $organization->id) === null) {
                $this->grantMembership->execute(
                    new GrantMembershipInput($operator->id, Role::Admin, $organization->id, $input->requestId),
                );
            }

            $backfilled++;
        }

        return new BackfillTenancyOutput($organization, $backfilled, false);
    }

    private function resolveDefaultOrganization(?string $requestId): Organization
    {
        // Adopt an existing organization (e.g. an A4-installed host); never mint a second.
        $existing = $this->organizations->all();

        if ($existing !== []) {
            return $existing[0];
        }

        $name = $this->resolveOrgName();

        return $this->createOrganization->execute(new CreateOrganizationInput(
            name: $name,
            slug: OrgSlugDeriver::derive($name, $this->suiteId),
            requestId: $requestId,
            externalId: $this->orgExternalId,
        ))->organization;
    }

    /**
     * Org display name: latest manifest body › latest completed session › literal default
     * (milestone A4/A5 spec). The manifest is reached through the session's manifest id, so
     * no new repository method is needed.
     */
    private function resolveOrgName(): string
    {
        $session = $this->sessions->findLatestCompleted();

        if ($session !== null && $session->installManifestId !== null) {
            $manifest = $this->manifests->findById($session->installManifestId);
            $fromManifest = $manifest?->body['org_display_name'] ?? null;

            if (is_string($fromManifest) && $fromManifest !== '') {
                return $fromManifest;
            }
        }

        if ($session !== null && $session->orgDisplayName !== null && $session->orgDisplayName !== '') {
            return $session->orgDisplayName;
        }

        return self::DEFAULT_ORG_NAME;
    }
}

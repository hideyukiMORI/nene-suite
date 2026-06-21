<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionRepositoryInterface;
use NeNeSuite\Tenancy\CreateOrganizationInput;
use NeNeSuite\Tenancy\CreateOrganizationUseCaseInterface;
use NeNeSuite\Tenancy\GrantMembershipInput;
use NeNeSuite\Tenancy\GrantMembershipUseCaseInterface;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;
use NeNeSuite\Tenancy\Role;

/**
 * Installer bootstrap (milestone A4): ensures the default organization exists and the
 * first operator can administer it.
 *
 * 1. Finds-or-creates the default organization (slug derived from `NENE_SUITE_ORG_NAME`)
 *    and stamps its minted `external_id` onto the install session, so `WriteEnvConfig`
 *    writes the real `NENE_SUITE_ORG_EXTERNAL_ID` and `CompleteInstallSession` records the
 *    same value in the manifest — env, manifest, session, and `organizations.external_id`
 *    all agree (replacing the old `suiteId` fallback).
 * 2. Grants the operator a platform `superadmin` membership (the cross-org platform plane)
 *    AND an `admin` membership in the default org (so a self-hosted single-org session has
 *    an org context — ADR 0015 §8 no-regress).
 *
 * Every step is lookup-first so re-running the installer reuses the existing org/memberships
 * without erroring (the A4 steps are idempotent; the installer as a whole is not).
 */
final readonly class BootstrapDefaultOrganizationUseCase implements BootstrapDefaultOrganizationUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
        private CreateOrganizationUseCaseInterface $createOrganization,
        private OrganizationRepositoryInterface $organizations,
        private GrantMembershipUseCaseInterface $grantMembership,
        private MembershipRepositoryInterface $memberships,
        private string $suiteId,
    ) {
    }

    public function execute(BootstrapDefaultOrganizationInput $input): BootstrapDefaultOrganizationOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        $slug = OrgSlugDeriver::derive($input->orgName, $this->suiteId);

        $organization = $this->organizations->findBySlug($slug)
            ?? $this->createOrganization->execute(
                new CreateOrganizationInput($input->orgName, $slug, $input->requestId),
            )->organization;

        $this->sessions->update($session->withOrgExternalId($organization->externalId, gmdate('Y-m-d\TH:i:s\Z')));

        if ($this->memberships->findByOperatorAndOrganization($input->operatorId, null) === null) {
            $this->grantMembership->execute(
                new GrantMembershipInput($input->operatorId, Role::Superadmin, null, $input->requestId),
            );
        }

        if ($this->memberships->findByOperatorAndOrganization($input->operatorId, $organization->id) === null) {
            $this->grantMembership->execute(
                new GrantMembershipInput($input->operatorId, Role::Admin, $organization->id, $input->requestId),
            );
        }

        return new BootstrapDefaultOrganizationOutput($organization);
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use NeNeSuite\Tenancy\GrantMembershipInput;
use NeNeSuite\Tenancy\GrantMembershipUseCaseInterface;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;
use NeNeSuite\Tenancy\Role;

/**
 * HTTP-facing operator provisioning (milestone A4.5): creates an apex operator, then
 * grants it an `admin` membership in the suite's default organization (resolved by
 * `NENE_SUITE_ORG_EXTERNAL_ID`).
 *
 * The `admin` role preserves pre-tenancy behavior — every operator was a full apex
 * administrator, so on a self-hosted single-org install a newly-created operator keeps
 * that authority (ADR 0015 §8 no-regress). Without this, an operator created via
 * `POST /api/v1/operators` would have zero memberships and be locked out once the session
 * JWT resolves membership → role (milestone A6).
 *
 * The installer does NOT use this path — it calls `CreateOperatorUseCase` directly before
 * the org exists, and `BootstrapDefaultOrganizationUseCase` grants the first operator's
 * memberships (A4). Operator creation and the membership grant are separate transactions
 * (the use cases each open their own); a rare partial failure leaves a recoverable
 * zero-membership operator, fixable via the superadmin console (A7) and tolerated by A6's
 * membership-resolution fallback.
 */
final readonly class ProvisionApexOperatorUseCase implements ProvisionApexOperatorUseCaseInterface
{
    public function __construct(
        private CreateOperatorUseCaseInterface $createOperator,
        private OrganizationRepositoryInterface $organizations,
        private GrantMembershipUseCaseInterface $grantMembership,
        private MembershipRepositoryInterface $memberships,
        private string $suiteOrgExternalId,
    ) {
    }

    public function execute(CreateOperatorInput $input): CreateOperatorOutput
    {
        $output = $this->createOperator->execute($input);
        $operator = $output->operator;

        $organization = $this->organizations->findByExternalId($this->suiteOrgExternalId);

        if (
            $organization !== null
            && $this->memberships->findByOperatorAndOrganization($operator->id, $organization->id) === null
        ) {
            $this->grantMembership->execute(
                new GrantMembershipInput($operator->id, Role::Admin, $organization->id, $input->requestId),
            );
        }

        return $output;
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Auth\TokenIssuerInterface;
use Nene2\Http\ClockInterface;
use Nene2\Http\UtcClock;
use NeNeSuite\Tenancy\MembershipRepositoryInterface;
use NeNeSuite\Tenancy\OrganizationNotFoundException;
use NeNeSuite\Tenancy\OrganizationRepositoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Re-scopes the operator's session to a different organization (milestone §7 ③) and issues a
 * fresh apex JWT carrying that organization's `org_external_id` + the operator's role there.
 * The platform `superadmin` dimension is recomputed from the platform membership (it never
 * changes on a switch). An operator may only switch to an organization they are a member of;
 * a missing organization or a missing membership both surface as 404 (organization-not-found),
 * so the API does not leak the existence of organizations to non-members.
 *
 * Like login ({@see CreateAuthSessionUseCase}), the new token gets a full TTL — a switch is a
 * session refresh, not an extension of any prior token's lifetime. The result reuses
 * {@see CreateAuthSessionOutput}: a switch produces a new auth session.
 */
final readonly class SwitchActiveOrganizationUseCase implements SwitchActiveOrganizationUseCaseInterface
{
    private const TOKEN_TTL_SECONDS = 86400;

    public function __construct(
        private OperatorRepositoryInterface $operators,
        private MembershipRepositoryInterface $memberships,
        private OrganizationRepositoryInterface $organizations,
        private TokenIssuerInterface $tokenIssuer,
        private string $suiteId,
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function execute(SwitchActiveOrganizationInput $input): CreateAuthSessionOutput
    {
        $operator = $this->operators->findById($input->operatorId);

        if ($operator === null) {
            // Token was valid but the operator was removed — treat as unauthenticated.
            throw new UnauthorizedException();
        }

        $organization = $this->organizations->findById($input->organizationId);
        $membership = $this->memberships->findByOperatorAndOrganization($input->operatorId, $input->organizationId);

        if ($organization === null || $membership === null) {
            // Unknown org, or an org the operator does not belong to — do not distinguish.
            throw new OrganizationNotFoundException($input->organizationId);
        }

        $isSuperadmin = $this->memberships->findByOperatorAndOrganization($input->operatorId, null) !== null;

        // A switch is a session refresh, so the new token gets a full TTL from a single read.
        $issuedAt = $this->clock->now()->getTimestamp();
        $expiresAt = $issuedAt + self::TOKEN_TTL_SECONDS;

        $token = $this->tokenIssuer->issue([
            'sub' => $operator->id,
            'suite_id' => $this->suiteId,
            'org_external_id' => $organization->externalId,
            'role' => $membership->role->value,
            'superadmin' => $isSuperadmin,
            'jti' => (string) new Ulid(),
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ]);

        return new CreateAuthSessionOutput(
            $token,
            $expiresAt,
            $operator,
            $organization->externalId,
            $membership->role,
            $isSuperadmin,
        );
    }
}

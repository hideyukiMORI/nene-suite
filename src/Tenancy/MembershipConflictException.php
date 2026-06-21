<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use RuntimeException;

/**
 * Raised when a membership already exists for an (operator, organization) pair — a
 * platform `superadmin` membership when `organizationId` is null. Use
 * ChangeMembershipRole to alter an existing membership's role. Mapped to HTTP 409
 * once the superadmin surface is added (milestone A7).
 */
final class MembershipConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $operatorId,
        public readonly ?string $organizationId,
    ) {
        $scope = $organizationId === null ? 'the platform' : "organization '{$organizationId}'";
        parent::__construct("Operator '{$operatorId}' already has a membership in {$scope}.");
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

/**
 * Lifecycle status of an organization in the Suite registry.
 *
 * Organizations are never hard-deleted when issued documents may exist in a
 * sibling app (ADR 0012 §5 / §11); removal is a soft `Disabled` only.
 */
enum OrganizationStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';
}

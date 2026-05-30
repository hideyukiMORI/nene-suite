<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

/**
 * Launcher SSOT role label (docs/explanation/terminology.md §7), derived from a
 * catalog app's `provides` tokens. Describes the sibling product's role; Suite
 * itself is never an SSOT.
 */
enum SsotRole: string
{
    case Billing = 'billing';
    case ReconciliationEvidence = 'reconciliation_evidence';
    case Cms = 'cms';
    case Archive = 'archive';
    case None = 'none';

    /**
     * @param list<string> $provides
     */
    public static function fromProvides(array $provides): self
    {
        foreach ($provides as $token) {
            $role = match ($token) {
                'billing-api' => self::Billing,
                'reconciliation-api' => self::ReconciliationEvidence,
                'cms-api' => self::Cms,
                'archive-api' => self::Archive,
                default => null,
            };

            if ($role !== null) {
                return $role;
            }
        }

        return self::None;
    }
}

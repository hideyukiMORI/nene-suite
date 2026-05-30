<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

/**
 * Deployment tier (docs/explanation/terminology.md §11). Phase 1 ships Tier B
 * (Docker / VPS) only; Tier A (shared hosting) extends this enum in Phase 3.
 */
enum InstallTier: string
{
    case B = 'B';
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

/**
 * The update standing of one installed product against its verified Origin manifest.
 *
 * - **up_to_date**       — installed ≥ latest.
 * - **update_available** — installed < latest (and not below the security floor).
 * - **forced**           — installed < `min_supported_version` (security floor) — surfaced distinctly.
 * - **unavailable**      — Origin could not be verified/reached for this product (degrade, never block).
 */
enum OriginUpdateStatus: string
{
    case UpToDate = 'up_to_date';
    case UpdateAvailable = 'update_available';
    case Forced = 'forced';
    case Unavailable = 'unavailable';
}

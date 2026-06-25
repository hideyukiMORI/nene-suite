<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;

/**
 * Returns the verified Origin update signals for the installed roster (or a disabled output when
 * Origin is unconfigured). Extracted so consumers — e.g. the catalog version mirror (ADR 0013 §4) —
 * can depend on the read without the concrete wiring.
 */
interface GetOriginUpdatesUseCaseInterface
{
    public function execute(DateTimeImmutable $now): OriginUpdatesOutput;
}

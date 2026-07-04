<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use DateTimeImmutable;
use NeNeSuite\Origin\GetOriginUpdatesUseCaseInterface;
use NeNeSuite\Origin\OriginUpdatesOutput;

final readonly class FixedOriginUpdatesUseCase implements GetOriginUpdatesUseCaseInterface
{
    public function __construct(
        private OriginUpdatesOutput $output,
    ) {
    }

    public function execute(DateTimeImmutable $now): OriginUpdatesOutput
    {
        return $this->output;
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\AppCatalog;

use DateTimeImmutable;
use NeNeSuite\Origin\GetOriginUpdatesUseCaseInterface;
use NeNeSuite\Origin\OriginUpdatesOutput;
use Throwable;

/**
 * Returns a preset Origin updates output (or throws) — lets the catalog version-source adapter be
 * tested without the real Origin fetch/verify pipeline.
 */
final readonly class FakeGetOriginUpdatesUseCase implements GetOriginUpdatesUseCaseInterface
{
    public function __construct(
        private ?OriginUpdatesOutput $output,
        private ?Throwable $failure = null,
    ) {
    }

    public function execute(DateTimeImmutable $now): OriginUpdatesOutput
    {
        if ($this->failure !== null) {
            throw $this->failure;
        }

        return $this->output ?? OriginUpdatesOutput::disabled();
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\AppSelection;

final readonly class UpdateAppSelectionInput
{
    /**
     * @param list<string> $selectedApps requested catalog ids (before resolution)
     */
    public function __construct(
        public string $installSessionId,
        public array $selectedApps,
        public ?string $requestId = null,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

/**
 * Operator-provided configuration for a Tier B suite install.
 * All values are read from env vars by `InstallerEnvReader`; none are interactive.
 */
final readonly class InstallerInput
{
    /**
     * @param list<string> $selectedApps catalog ids to install (dependency-resolved by UpdateAppSelectionUseCase)
     */
    public function __construct(
        public string $baseUrl,
        public string $orgName,
        public array $selectedApps,
        public string $disclaimerVersion,
        public string $operatorEmail,
        public string $operatorPassword,
        public ?string $operatorDisplayName = null,
    ) {
    }
}

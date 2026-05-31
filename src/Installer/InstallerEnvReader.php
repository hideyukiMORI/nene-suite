<?php

declare(strict_types=1);

namespace NeNeSuite\Installer;

use RuntimeException;

/**
 * Reads Tier B installer configuration from environment variables.
 * All required vars must be set before running `installer/install.php`.
 */
final readonly class InstallerEnvReader
{
    private const DISCLAIMER_VERSION_DEFAULT = '2026-05-31';

    public function read(): InstallerInput
    {
        $baseUrl = $this->require('NENE_SUITE_BASE_URL');
        $orgName = $this->require('NENE_SUITE_ORG_NAME');
        $installedApps = $this->require('NENE_SUITE_INSTALLED_APPS');
        $operatorEmail = $this->require('NENE_SUITE_APEX_OPERATOR_EMAIL');
        $operatorPassword = $this->require('NENE_SUITE_APEX_OPERATOR_PASSWORD');

        $accepted = $this->optional('NENE_SUITE_DISCLAIMER_ACCEPTED');
        if ($accepted !== '1') {
            throw new RuntimeException(
                'Set NENE_SUITE_DISCLAIMER_ACCEPTED=1 to confirm you have read ' .
                'docs/explanation/disclaimer.md before running the installer.',
            );
        }

        $selectedApps = array_values(array_filter(
            array_map('trim', explode(',', $installedApps)),
        ));

        if ($selectedApps === []) {
            throw new RuntimeException('NENE_SUITE_INSTALLED_APPS must list at least one catalog id.');
        }

        return new InstallerInput(
            baseUrl: $baseUrl,
            orgName: $orgName,
            selectedApps: $selectedApps,
            disclaimerVersion: $this->optional('NENE_SUITE_DISCLAIMER_VERSION') ?? self::DISCLAIMER_VERSION_DEFAULT,
            operatorEmail: $operatorEmail,
            operatorPassword: $operatorPassword,
            operatorDisplayName: $this->optional('NENE_SUITE_APEX_OPERATOR_NAME'),
        );
    }

    private function require(string $key): string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        if (!is_string($value) || $value === '') {
            throw new RuntimeException("Required env var {$key} is not set.");
        }

        return $value;
    }

    private function optional(string $key): ?string
    {
        $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}

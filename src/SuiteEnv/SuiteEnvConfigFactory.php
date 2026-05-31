<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

/**
 * Builds a `SuiteEnvConfig` from the install session's resolved data and the
 * operator-supplied runtime parameters (base URL, JWT secret).
 * The issuer URL is derived from the base URL per the suite env contract §Orchestrator.
 */
final readonly class SuiteEnvConfigFactory
{
    /**
     * @param list<string>          $installedApps catalog ids from the completed install session
     * @param array<string, string> $appUrls       catalogId → configured public URL
     */
    public function create(
        string $suiteId,
        string $orgExternalId,
        ?string $orgName,
        string $baseUrl,
        string $jwtSecret,
        array $installedApps,
        array $appUrls,
    ): SuiteEnvConfig {
        $apexUrl = $baseUrl;
        $issuerUrl = rtrim($baseUrl, '/') . '/api/auth';

        return new SuiteEnvConfig(
            suiteId: $suiteId,
            baseUrl: $baseUrl,
            apexUrl: $apexUrl,
            issuerUrl: $issuerUrl,
            jwtSecret: $jwtSecret,
            orgExternalId: $orgExternalId,
            orgName: $orgName,
            installedApps: $installedApps,
            appUrls: $appUrls,
        );
    }
}

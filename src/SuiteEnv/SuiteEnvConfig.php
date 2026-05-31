<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteEnv;

/**
 * Immutable snapshot of all NENE_SUITE_* variables for one suite installation.
 * `toEnvMap()` returns the full set (including secret) for file writing.
 * `toSanitizedMap()` replaces the JWT secret with `[REDACTED]` for audit output.
 *
 * Key names and semantics: docs/explanation/suite-environment-contract.md (binding).
 */
final readonly class SuiteEnvConfig
{
    /**
     * @param list<string>            $installedApps catalog ids of selected apps
     * @param array<string, string>   $appUrls       catalogId → public URL
     */
    public function __construct(
        public string $suiteId,
        public string $baseUrl,
        public string $apexUrl,
        public string $issuerUrl,
        public string $jwtSecret,
        public string $orgExternalId,
        public ?string $orgName,
        public array $installedApps,
        public array $appUrls,
    ) {
    }

    /**
     * Returns all NENE_SUITE_* key–value pairs including the plaintext JWT secret.
     * Used only by the file writer; never passed to the audit recorder.
     *
     * @return array<string, string>
     */
    public function toEnvMap(): array
    {
        $map = [
            'NENE_SUITE_MODE'           => '1',
            'NENE_SUITE_ID'             => $this->suiteId,
            'NENE_SUITE_BASE_URL'       => $this->baseUrl,
            'NENE_SUITE_APEX_URL'       => $this->apexUrl,
            'NENE_SUITE_ISSUER_URL'     => $this->issuerUrl,
            'NENE_SUITE_JWT_SECRET'     => $this->jwtSecret,
            'NENE_SUITE_ORG_EXTERNAL_ID' => $this->orgExternalId,
        ];

        if ($this->orgName !== null) {
            $map['NENE_SUITE_ORG_NAME'] = $this->orgName;
        }

        if ($this->installedApps !== []) {
            $map['NENE_SUITE_INSTALLED_APPS'] = implode(',', $this->installedApps);
        }

        foreach ($this->appUrls as $catalogId => $url) {
            $snake = strtoupper(str_replace('-', '_', $catalogId));
            $map["NENE_SUITE_APP_{$snake}_URL"] = $url;
        }

        return $map;
    }

    /**
     * Same as `toEnvMap()` but replaces NENE_SUITE_JWT_SECRET with `[REDACTED]`
     * (audit-trail §5). Safe to pass to audit recorders and API responses.
     *
     * @return array<string, string>
     */
    public function toSanitizedMap(): array
    {
        $map = $this->toEnvMap();
        $map['NENE_SUITE_JWT_SECRET'] = '[REDACTED]';

        return $map;
    }
}

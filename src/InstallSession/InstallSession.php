<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

/**
 * Installer wizard run, persisted in the nene_suite control database. Holds no
 * secrets. Timestamps are ISO-8601 UTC strings (e.g. 2026-05-30T09:48:46Z).
 */
final readonly class InstallSession
{
    /**
     * @param list<string> $selectedApps catalog ids, dependency-resolved
     */
    public function __construct(
        public string $id,
        public string $suiteId,
        public InstallSessionStatus $status,
        public InstallTier $tier,
        public int $catalogRevision,
        public array $selectedApps,
        public bool $disclaimerAccepted,
        public ?string $disclaimerAcceptedAt,
        public ?string $orgExternalId,
        public ?string $orgDisplayName,
        public ?string $installManifestId,
        public ?string $failureCode,
        public string $createdAt,
        public string $updatedAt,
        public ?string $completedAt,
    ) {
    }
}

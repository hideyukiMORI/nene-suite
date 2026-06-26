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
     * @param list<string>                     $selectedApps    catalog ids, dependency-resolved
     * @param list<AppDatabaseTargetSelection> $databaseTargets per-app database target overrides (ADR 0022 mode A); empty = env/default targets
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
        public array $databaseTargets = [],
    ) {
    }

    /**
     * The operator's database target override for one app, or null when the app
     * carries no override (the resolver then falls back to env / default).
     */
    public function databaseTargetFor(string $catalogId): ?AppDatabaseTargetSelection
    {
        foreach ($this->databaseTargets as $selection) {
            if ($selection->catalogId === $catalogId) {
                return $selection;
            }
        }

        return null;
    }

    /**
     * @param list<string> $selectedApps
     */
    public function withSelectedApps(array $selectedApps, string $updatedAt): self
    {
        return new self(
            id: $this->id,
            suiteId: $this->suiteId,
            status: $this->status,
            tier: $this->tier,
            catalogRevision: $this->catalogRevision,
            selectedApps: $selectedApps,
            disclaimerAccepted: $this->disclaimerAccepted,
            disclaimerAcceptedAt: $this->disclaimerAcceptedAt,
            orgExternalId: $this->orgExternalId,
            orgDisplayName: $this->orgDisplayName,
            installManifestId: $this->installManifestId,
            failureCode: $this->failureCode,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            completedAt: $this->completedAt,
            databaseTargets: $this->databaseTargets,
        );
    }

    public function withDisclaimerAccepted(string $acceptedAt, string $updatedAt): self
    {
        return new self(
            id: $this->id,
            suiteId: $this->suiteId,
            status: $this->status,
            tier: $this->tier,
            catalogRevision: $this->catalogRevision,
            selectedApps: $this->selectedApps,
            disclaimerAccepted: true,
            disclaimerAcceptedAt: $acceptedAt,
            orgExternalId: $this->orgExternalId,
            orgDisplayName: $this->orgDisplayName,
            installManifestId: $this->installManifestId,
            failureCode: $this->failureCode,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            completedAt: $this->completedAt,
            databaseTargets: $this->databaseTargets,
        );
    }

    public function withOrgExternalId(string $orgExternalId, string $updatedAt): self
    {
        return new self(
            id: $this->id,
            suiteId: $this->suiteId,
            status: $this->status,
            tier: $this->tier,
            catalogRevision: $this->catalogRevision,
            selectedApps: $this->selectedApps,
            disclaimerAccepted: $this->disclaimerAccepted,
            disclaimerAcceptedAt: $this->disclaimerAcceptedAt,
            orgExternalId: $orgExternalId,
            orgDisplayName: $this->orgDisplayName,
            installManifestId: $this->installManifestId,
            failureCode: $this->failureCode,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            completedAt: $this->completedAt,
            databaseTargets: $this->databaseTargets,
        );
    }

    public function withCompleted(string $installManifestId, string $orgExternalId, string $completedAt, string $updatedAt): self
    {
        return new self(
            id: $this->id,
            suiteId: $this->suiteId,
            status: InstallSessionStatus::Completed,
            tier: $this->tier,
            catalogRevision: $this->catalogRevision,
            selectedApps: $this->selectedApps,
            disclaimerAccepted: $this->disclaimerAccepted,
            disclaimerAcceptedAt: $this->disclaimerAcceptedAt,
            orgExternalId: $orgExternalId,
            orgDisplayName: $this->orgDisplayName,
            installManifestId: $installManifestId,
            failureCode: $this->failureCode,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            completedAt: $completedAt,
            databaseTargets: $this->databaseTargets,
        );
    }

    public function withFailure(string $failureCode, string $updatedAt): self
    {
        return new self(
            id: $this->id,
            suiteId: $this->suiteId,
            status: InstallSessionStatus::Failed,
            tier: $this->tier,
            catalogRevision: $this->catalogRevision,
            selectedApps: $this->selectedApps,
            disclaimerAccepted: $this->disclaimerAccepted,
            disclaimerAcceptedAt: $this->disclaimerAcceptedAt,
            orgExternalId: $this->orgExternalId,
            orgDisplayName: $this->orgDisplayName,
            installManifestId: $this->installManifestId,
            failureCode: $failureCode,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            completedAt: $this->completedAt,
            databaseTargets: $this->databaseTargets,
        );
    }

    /**
     * @param list<AppDatabaseTargetSelection> $databaseTargets
     */
    public function withDatabaseTargets(array $databaseTargets, string $updatedAt): self
    {
        return new self(
            id: $this->id,
            suiteId: $this->suiteId,
            status: $this->status,
            tier: $this->tier,
            catalogRevision: $this->catalogRevision,
            selectedApps: $this->selectedApps,
            disclaimerAccepted: $this->disclaimerAccepted,
            disclaimerAcceptedAt: $this->disclaimerAcceptedAt,
            orgExternalId: $this->orgExternalId,
            orgDisplayName: $this->orgDisplayName,
            installManifestId: $this->installManifestId,
            failureCode: $this->failureCode,
            createdAt: $this->createdAt,
            updatedAt: $updatedAt,
            completedAt: $this->completedAt,
            databaseTargets: $databaseTargets,
        );
    }
}

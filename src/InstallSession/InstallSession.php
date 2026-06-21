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
        );
    }
}

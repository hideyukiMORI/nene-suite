<?php

declare(strict_types=1);

namespace NeNeSuite\Tests\Deploy;

use NeNeSuite\AppCatalog\Catalog;
use NeNeSuite\AppCatalog\CatalogApp;
use NeNeSuite\Deploy\CreateDeployRequestInput;
use NeNeSuite\Deploy\CreateDeployRequestUseCase;
use NeNeSuite\Deploy\DeployAgentConfig;
use NeNeSuite\Deploy\DeployCapabilityDisabledException;
use NeNeSuite\Deploy\DeployRequestStatus;
use NeNeSuite\Deploy\DeployValidationException;
use NeNeSuite\Tests\AppCatalog\InMemoryCatalogAppRepository;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorder;
use NeNeSuite\Tests\SuiteAudit\RecordingSuiteAuditRecorderFactory;
use NeNeSuite\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class CreateDeployRequestUseCaseTest extends TestCase
{
    private const SUITE_ID = '01J8XRDEV000000000000000ZA';
    private const OPERATOR_ID = '01J8XRDOP000000000000000ZA';
    private const DIGEST = 'sha256:9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08';

    public function testThrowsWhileCapabilityIsDisabled(): void
    {
        $useCase = $this->useCase(DeployAgentConfig::disabled());

        $this->expectException(DeployCapabilityDisabledException::class);

        $useCase->execute(new CreateDeployRequestInput('nene-invoice', self::DIGEST, self::OPERATOR_ID));
    }

    public function testRejectsNonCatalogService(): void
    {
        $useCase = $this->useCase();

        $this->expectException(DeployValidationException::class);

        $useCase->execute(new CreateDeployRequestInput('suite', self::DIGEST, self::OPERATOR_ID));
    }

    public function testRejectsMutableTagAsImageReference(): void
    {
        $useCase = $this->useCase();

        $this->expectException(DeployValidationException::class);

        $useCase->execute(new CreateDeployRequestInput('nene-invoice', 'nene-invoice:latest', self::OPERATOR_ID));
    }

    public function testRejectsUppercaseDigestHex(): void
    {
        $useCase = $this->useCase();

        $this->expectException(DeployValidationException::class);

        $useCase->execute(new CreateDeployRequestInput(
            'nene-invoice',
            'sha256:' . strtoupper(substr(self::DIGEST, 7)),
            self::OPERATOR_ID,
        ));
    }

    public function testPersistsPendingRequestAndRecordsAudit(): void
    {
        $repository = new InMemoryDeployRequestRepository();
        $recorder = new RecordingSuiteAuditRecorder();
        $useCase = $this->useCase(repository: $repository, recorder: $recorder);

        $output = $useCase->execute(new CreateDeployRequestInput(
            'nene-invoice',
            self::DIGEST,
            self::OPERATOR_ID,
            'req-123',
        ));

        $request = $output->request;
        self::assertSame('nene-invoice', $request->service);
        self::assertSame(self::DIGEST, $request->imageDigest);
        self::assertSame(DeployRequestStatus::Pending, $request->status);
        self::assertSame(self::OPERATOR_ID, $request->requestedBy);
        self::assertNull($request->detail);
        self::assertNull($request->completedAt);
        self::assertNotNull($repository->findById($request->id));

        self::assertCount(1, $recorder->commands);
        $event = $recorder->commands[0];
        self::assertSame('deploy_request.created', $event->action);
        self::assertSame('deploy_request', $event->entityType);
        self::assertSame($request->id, $event->entityId);
        self::assertNull($event->beforeJson);
        self::assertNotNull($event->afterJson);
        self::assertSame('pending', $event->afterJson['status']);
        self::assertSame(self::OPERATOR_ID, $event->actorUserId);
        self::assertSame('apex_admin', $event->source);
        self::assertSame('req-123', $event->requestId);
    }

    private function useCase(
        ?DeployAgentConfig $config = null,
        ?InMemoryDeployRequestRepository $repository = null,
        ?RecordingSuiteAuditRecorder $recorder = null,
    ): CreateDeployRequestUseCase {
        return new CreateDeployRequestUseCase(
            $config ?? new DeployAgentConfig(true, str_repeat('k', 32)),
            new InMemoryCatalogAppRepository($this->catalog()),
            new ImmediateTransactionManager(),
            new InMemoryDeployRequestRepositoryFactory($repository ?? new InMemoryDeployRequestRepository()),
            new RecordingSuiteAuditRecorderFactory($recorder ?? new RecordingSuiteAuditRecorder()),
            self::SUITE_ID,
        );
    }

    private function catalog(): Catalog
    {
        return new Catalog(1, [
            new CatalogApp('nene-invoice', 'NeNe Invoice', null, 'nene-invoice', 'installable', [], ['billing-api'], '/install/index.php', 'NENE_INVOICE_DB_'),
            new CatalogApp('nene-clear', 'NeNe Clear', null, 'nene-clear', 'installable', ['nene-invoice'], ['reconciliation-api'], '/install/index.php', 'NENE_CLEAR_DB_'),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\AppCatalog\CatalogAppRepositoryInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;
use Symfony\Component\Uid\Ulid;

/**
 * Persists one pending deploy request for the host-side agent (ADR 0019 OQ1, S2-1a).
 * Gate order: capability flag (409) → catalog allow-list (422) → digest shape (422).
 * In a single transaction the row is inserted and `deploy_request.created` is recorded
 * with before `null` (ADR 0007). The suite never executes the deployment itself.
 */
final readonly class CreateDeployRequestUseCase implements CreateDeployRequestUseCaseInterface
{
    private const IMAGE_DIGEST_PATTERN = '/^sha256:[0-9a-f]{64}$/';

    public function __construct(
        private DeployAgentConfig $config,
        private CatalogAppRepositoryInterface $catalog,
        private DatabaseTransactionManagerInterface $transactions,
        private DeployRequestRepositoryFactoryInterface $repositories,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(CreateDeployRequestInput $input): CreateDeployRequestOutput
    {
        if (!$this->config->enabled) {
            throw new DeployCapabilityDisabledException();
        }

        $allowed = false;

        foreach ($this->catalog->load()->apps as $app) {
            if ($app->id === $input->service) {
                $allowed = true;

                break;
            }
        }

        if (!$allowed) {
            throw new DeployValidationException(
                'service',
                sprintf("'%s' is not a catalog app id (deploys are allow-listed).", $input->service),
            );
        }

        if (preg_match(self::IMAGE_DIGEST_PATTERN, $input->imageDigest) !== 1) {
            throw new DeployValidationException(
                'imageDigest',
                'The image pin must be an immutable sha256:<64 hex> digest, never a mutable tag.',
            );
        }

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $request = new DeployRequest(
            id: (string) new Ulid(),
            service: $input->service,
            imageDigest: $input->imageDigest,
            status: DeployRequestStatus::Pending,
            requestedBy: $input->operatorId,
            detail: null,
            createdAt: $now,
            updatedAt: $now,
            completedAt: null,
        );

        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($request, $input): CreateDeployRequestOutput {
                $this->repositories->create($query)->insert($request);

                $this->audit->create($query)->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'deploy_request.created',
                    entityType: 'deploy_request',
                    entityId: $request->id,
                    beforeJson: null,
                    afterJson: DeployRequestView::toArray($request),
                    actorUserId: $input->operatorId,
                    source: 'apex_admin',
                    requestId: $input->requestId,
                ));

                return new CreateDeployRequestOutput($request);
            },
        );
    }
}

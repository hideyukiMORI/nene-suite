<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;
use NeNeSuite\SuiteAudit\RecordSuiteAuditEventCommand;
use NeNeSuite\SuiteAudit\SuiteAuditRecorderFactoryInterface;

/**
 * Terminal transition `pending → succeeded | failed`, reported by the host-side agent.
 * Single transaction: load → guard (terminal rows conflict, the agent must not re-execute)
 * → update → `deploy_request.completed` with before/after and a machine actor label
 * (source `api`, `actor_user_id` NULL — audit-trail §3). Independent outcome verification
 * via the sibling `/machine/health` probe is S2-1c, not here.
 */
final readonly class ReportDeployResultUseCase implements ReportDeployResultUseCaseInterface
{
    private const ACTOR_LABEL = 'deploy-agent';

    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private DeployRequestRepositoryFactoryInterface $repositories,
        private SuiteAuditRecorderFactoryInterface $audit,
        private string $suiteId,
    ) {
    }

    public function execute(ReportDeployResultInput $input): ReportDeployResultOutput
    {
        if (!$input->status->isTerminal()) {
            throw new DeployValidationException('status', 'A reported result must be succeeded or failed.');
        }

        return $this->transactions->transactional(
            function (DatabaseQueryExecutorInterface $query) use ($input): ReportDeployResultOutput {
                $requests = $this->repositories->create($query);
                $existing = $requests->findById($input->deployRequestId);

                if ($existing === null) {
                    throw new DeployRequestNotFoundException($input->deployRequestId);
                }

                if ($existing->status->isTerminal()) {
                    throw new DeployRequestConflictException($existing->id);
                }

                $now = gmdate('Y-m-d\TH:i:s\Z');
                $completed = new DeployRequest(
                    id: $existing->id,
                    service: $existing->service,
                    imageDigest: $existing->imageDigest,
                    status: $input->status,
                    requestedBy: $existing->requestedBy,
                    detail: $input->detail,
                    createdAt: $existing->createdAt,
                    updatedAt: $now,
                    completedAt: $now,
                );

                $requests->update($completed);

                $this->audit->create($query)->record(new RecordSuiteAuditEventCommand(
                    suiteId: $this->suiteId,
                    action: 'deploy_request.completed',
                    entityType: 'deploy_request',
                    entityId: $completed->id,
                    beforeJson: DeployRequestView::toArray($existing),
                    afterJson: DeployRequestView::toArray($completed),
                    actorUserId: null,
                    actorLabel: self::ACTOR_LABEL,
                    source: 'api',
                    requestId: $input->requestId,
                ));

                return new ReportDeployResultOutput($completed);
            },
        );
    }
}

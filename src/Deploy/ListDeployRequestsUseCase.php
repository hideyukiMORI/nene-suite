<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;

/**
 * Operator read model (S2-1d feeds from this). Always answers — `enabled` mirrors the
 * capability flag so the UI degrades to manual-apply guidance without a second probe;
 * a disabled suite simply has no rows (requests are never created while disabled).
 */
final readonly class ListDeployRequestsUseCase implements ListDeployRequestsUseCaseInterface
{
    public function __construct(
        private DeployAgentConfig $config,
        private DatabaseTransactionManagerInterface $transactions,
        private DeployRequestRepositoryFactoryInterface $repositories,
    ) {
    }

    public function execute(ListDeployRequestsInput $input): ListDeployRequestsOutput
    {
        return $this->transactions->transactional(
            fn (DatabaseQueryExecutorInterface $query): ListDeployRequestsOutput => new ListDeployRequestsOutput(
                $this->config->enabled,
                $this->repositories->create($query)->findRecent($input->status, $input->limit),
            ),
        );
    }
}

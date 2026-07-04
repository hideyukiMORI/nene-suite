<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Database\DatabaseQueryExecutorInterface;
use Nene2\Database\DatabaseTransactionManagerInterface;

/**
 * Agent poll (FIFO). Authentication happens in the handler ({@see DeployAgentAuthenticator});
 * this use case only reads. Oldest first so the agent executes in request order.
 */
final readonly class ListPendingDeployRequestsUseCase implements ListPendingDeployRequestsUseCaseInterface
{
    public function __construct(
        private DatabaseTransactionManagerInterface $transactions,
        private DeployRequestRepositoryFactoryInterface $repositories,
    ) {
    }

    public function execute(): ListPendingDeployRequestsOutput
    {
        return $this->transactions->transactional(
            fn (DatabaseQueryExecutorInterface $query): ListPendingDeployRequestsOutput => new ListPendingDeployRequestsOutput(
                $this->repositories->create($query)->findPending(),
            ),
        );
    }
}

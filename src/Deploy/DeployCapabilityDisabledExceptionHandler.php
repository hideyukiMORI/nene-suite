<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class DeployCapabilityDisabledExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof DeployCapabilityDisabledException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'deploy-capability-disabled',
            'Deploy capability disabled',
            409,
            'The host-side deploy agent capability is not enabled on this suite (NENE_SUITE_DEPLOY_AGENT_ENABLED).',
        );
    }
}

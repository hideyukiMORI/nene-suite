<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class DeployAgentUnauthorizedExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof DeployAgentUnauthorizedException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        return $this->problemDetails->create(
            $request,
            'deploy-agent-unauthorized',
            'Deploy agent unauthorized',
            401,
            'A valid X-NENE-SUITE-DEPLOY-KEY header is required.',
        );
    }
}

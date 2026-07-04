<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class DeployValidationExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof DeployValidationException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $detail = $exception instanceof DeployValidationException
            ? $exception->detail
            : 'The deploy request data is invalid.';

        return $this->problemDetails->create(
            $request,
            'deploy-validation-failed',
            'Deploy request validation failed',
            422,
            $detail,
        );
    }
}

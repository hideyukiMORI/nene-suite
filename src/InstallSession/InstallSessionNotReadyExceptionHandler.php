<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class InstallSessionNotReadyExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof InstallSessionNotReadyException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        if (!$exception instanceof InstallSessionNotReadyException) {
            return $this->problemDetails->create($request, 'internal-error', 'Internal Server Error', 500, 'Unexpected error.');
        }

        return $this->problemDetails->create(
            $request,
            $exception->problemSlug,
            $exception->problemTitle,
            422,
            $exception->getMessage(),
        );
    }
}

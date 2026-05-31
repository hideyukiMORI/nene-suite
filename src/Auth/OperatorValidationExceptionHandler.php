<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class OperatorValidationExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof OperatorValidationException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $detail = $exception instanceof OperatorValidationException ? $exception->detail : $exception->getMessage();

        return $this->problemDetails->create(
            $request,
            'operator-validation-failed',
            'Operator validation failed',
            422,
            $detail,
        );
    }
}

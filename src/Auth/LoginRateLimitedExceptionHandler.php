<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class LoginRateLimitedExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof LoginRateLimitedException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $retryAfter = $exception instanceof LoginRateLimitedException
            ? $exception->retryAfterSeconds
            : LoginRateLimiter::DEFAULT_WINDOW_SECONDS;

        return $this->problemDetails
            ->create(
                $request,
                'rate-limited',
                'Too many requests',
                429,
                'Too many login attempts. Please retry later.',
            )
            ->withHeader('Retry-After', (string) $retryAfter);
    }
}

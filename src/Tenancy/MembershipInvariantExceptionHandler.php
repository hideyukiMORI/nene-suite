<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class MembershipInvariantExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof MembershipInvariantException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $detail = $exception instanceof MembershipInvariantException
            ? $exception->detail
            : 'This change would violate a membership invariant.';

        return $this->problemDetails->create(
            $request,
            'membership-invariant',
            'Membership invariant violated',
            409,
            $detail,
        );
    }
}

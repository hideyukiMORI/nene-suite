<?php

declare(strict_types=1);

namespace NeNeSuite\Tenancy;

use Nene2\Error\DomainExceptionHandlerInterface;
use Nene2\Error\ProblemDetailsResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final readonly class MembershipValidationExceptionHandler implements DomainExceptionHandlerInterface
{
    public function __construct(
        private ProblemDetailsResponseFactory $problemDetails,
    ) {
    }

    public function supports(Throwable $exception): bool
    {
        return $exception instanceof MembershipValidationException;
    }

    public function handle(Throwable $exception, ServerRequestInterface $request): ResponseInterface
    {
        $detail = $exception instanceof MembershipValidationException
            ? $exception->detail
            : 'The membership data is invalid.';

        return $this->problemDetails->create(
            $request,
            'membership-validation-failed',
            'Membership validation failed',
            422,
            $detail,
        );
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Tenancy\SuperadminGuard;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/operators — operationId listOperators. Platform-superadmin only: listing every
 * operator (their emails) is a cross-tenant/platform read, so it is gated by the superadmin
 * plane rather than plain operator auth. Read-only; no audit event.
 */
final readonly class ListOperatorsHandler
{
    public function __construct(
        private SuperadminGuard $guard,
        private ListOperatorsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->guard->ensure($request);

        $output = $this->useCase->execute();

        return $this->response->create([
            'operators' => array_map(
                static fn (Operator $operator): array => OperatorView::toArray($operator),
                $output->operators,
            ),
        ]);
    }
}

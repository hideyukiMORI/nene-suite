<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use DateTimeImmutable;
use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/origin/updates — operationId getOriginUpdates. Operator-authenticated, read-only
 * (no audit event). Returns `available:false` with an empty list when Origin is not configured.
 */
final readonly class GetOriginUpdatesHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private GetOriginUpdatesUseCase $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticator->operatorId($request);

        $output = $this->useCase->execute(new DateTimeImmutable('now'));

        return $this->response->create([
            'available' => $output->available,
            'updates' => array_map(
                static fn (OriginUpdateSignal $signal): array => OriginUpdateSignalView::toArray($signal),
                $output->updates,
            ),
        ]);
    }
}

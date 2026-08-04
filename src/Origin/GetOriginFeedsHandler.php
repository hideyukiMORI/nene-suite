<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use Nene2\Http\ClockInterface;
use Nene2\Http\JsonResponseFactory;
use Nene2\Http\UtcClock;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/origin/announcements and /api/v1/origin/house-ads — operator-authenticated, read-only.
 * One handler; the route supplies the feed kind. `?locale=` is constrained to the locales Suite
 * renders (en | ja, default en); the reader handles the missing-locale → en fallback.
 */
final readonly class GetOriginFeedsHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private GetOriginFeedsUseCase $useCase,
        private JsonResponseFactory $response,
        private ClockInterface $clock = new UtcClock(),
    ) {
    }

    public function handle(ServerRequestInterface $request, OriginFeedKind $kind): ResponseInterface
    {
        $this->authenticator->operatorId($request);

        $output = $this->useCase->execute($kind, $this->locale($request), $this->clock->now());

        return $this->response->create([
            'available' => $output->available,
            'feeds' => array_map(
                static fn (OriginFeed $feed): array => OriginFeedView::toArray($feed),
                $output->feeds,
            ),
        ]);
    }

    private function locale(ServerRequestInterface $request): string
    {
        $params = $request->getQueryParams();
        $locale = is_string($params['locale'] ?? null) ? $params['locale'] : 'en';

        // Suite renders en + ja only; anything else degrades to en.
        return $locale === 'ja' ? 'ja' : 'en';
    }
}

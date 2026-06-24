<?php

declare(strict_types=1);

namespace NeNeSuite\Origin;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

final readonly class OriginRouteRegistrar
{
    public function __construct(
        private GetOriginUpdatesHandler $updatesHandler,
        private GetOriginFeedsHandler $feedsHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $updatesHandler = $this->updatesHandler;
        $feedsHandler = $this->feedsHandler;

        $router->get(
            '/api/v1/origin/updates',
            static fn (ServerRequestInterface $request) => $updatesHandler->handle($request),
        );

        $router->get(
            '/api/v1/origin/announcements',
            static fn (ServerRequestInterface $request) => $feedsHandler->handle($request, OriginFeedKind::Announcement),
        );

        $router->get(
            '/api/v1/origin/house-ads',
            static fn (ServerRequestInterface $request) => $feedsHandler->handle($request, OriginFeedKind::Ad),
        );
    }
}

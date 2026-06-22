<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

use Nene2\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Registers the hosted-edition federation routes. Added to the application's route registrars only
 * when the edition is hosted ({@see \NeNeSuite\ApplicationServiceProvider}), so the JWKS endpoint is
 * never served (404) in a self-hosted (OSS) install.
 */
final readonly class FederationRouteRegistrar
{
    public function __construct(
        private FederationJwksHandler $jwksHandler,
    ) {
    }

    public function __invoke(Router $router): void
    {
        $jwksHandler = $this->jwksHandler;

        $router->get(
            '/.well-known/jwks.json',
            static fn (ServerRequestInterface $request) => $jwksHandler->handle($request),
        );
    }
}

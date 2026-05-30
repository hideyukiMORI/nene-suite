<?php

declare(strict_types=1);

namespace NeNeSuite\InstalledApps;

use Nene2\Http\JsonResponseFactory;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/installed-apps — operationId listInstalledApps. Operator-authenticated.
 */
final readonly class ListInstalledAppsHandler
{
    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private ListInstalledAppsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticator->operatorId($request);

        $output = $this->useCase->execute();

        return $this->response->create([
            'apps' => array_map(
                static fn (InstalledApp $app): array => InstalledAppView::toArray($app),
                $output->apps,
            ),
        ]);
    }
}

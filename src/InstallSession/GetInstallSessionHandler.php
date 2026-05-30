<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Http\JsonResponseFactory;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/install-sessions/{installSessionId} — operationId getInstallSession.
 */
final readonly class GetInstallSessionHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private GetInstallSessionUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = is_array($parameters) && is_string($parameters['installSessionId'] ?? null)
            ? $parameters['installSessionId']
            : '';

        if (preg_match(self::ULID_PATTERN, $id) !== 1) {
            throw new InstallSessionNotFoundException($id);
        }

        $output = $this->useCase->execute(new GetInstallSessionInput($id));

        return $this->response->create(InstallSessionView::toArray($output->session));
    }
}

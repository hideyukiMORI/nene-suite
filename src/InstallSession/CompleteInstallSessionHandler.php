<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/install-sessions/{installSessionId}/complete — operationId completeInstallSession.
 */
final readonly class CompleteInstallSessionHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private CompleteInstallSessionUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
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

        $requestId = $this->requestIdHolder->get();

        $output = $this->useCase->execute(new CompleteInstallSessionInput(
            installSessionId: $id,
            requestId: $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(InstallSessionView::toArray($output->session));
    }
}

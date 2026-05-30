<?php

declare(strict_types=1);

namespace NeNeSuite\AppSelection;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PUT /api/v1/install-sessions/{installSessionId}/app-selection — operationId updateAppSelection.
 */
final readonly class UpdateAppSelectionHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private UpdateAppSelectionUseCaseInterface $useCase,
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

        $body = JsonRequestBodyParser::parse($request);
        $selectedApps = $this->parseSelectedApps($body['selectedApps'] ?? null);

        $requestId = $this->requestIdHolder->get();

        $output = $this->useCase->execute(new UpdateAppSelectionInput(
            installSessionId: $id,
            selectedApps: $selectedApps,
            requestId: $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(InstallSessionView::toArray($output->session));
    }

    /**
     * @return list<string>
     */
    private function parseSelectedApps(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new ValidationException([
                new ValidationError('selectedApps', 'selectedApps must be an array of catalog ids.', 'invalid_type'),
            ]);
        }

        $apps = [];

        foreach ($value as $app) {
            if (!is_string($app)) {
                throw new ValidationException([
                    new ValidationError('selectedApps', 'selectedApps entries must be strings.', 'invalid_type'),
                ]);
            }

            $apps[] = $app;
        }

        return $apps;
    }
}

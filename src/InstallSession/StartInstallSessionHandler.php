<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/install-sessions — operationId startInstallSession.
 */
final readonly class StartInstallSessionHandler
{
    public function __construct(
        private StartInstallSessionUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $body = JsonRequestBodyParser::parse($request);

        $errors = [];

        $tierRaw = is_string($body['tier'] ?? null) ? trim((string) $body['tier']) : '';
        $tier = InstallTier::tryFrom($tierRaw);

        if ($tier === null) {
            $errors[] = new ValidationError('tier', 'tier must be one of: B', 'invalid_enum');
        }

        $selectedApps = $this->parseSelectedApps($body['selectedApps'] ?? null, $errors);

        $orgDisplayName = null;
        if (isset($body['orgDisplayName'])) {
            if (!is_string($body['orgDisplayName'])) {
                $errors[] = new ValidationError('orgDisplayName', 'orgDisplayName must be a string.', 'invalid_type');
            } else {
                $orgDisplayName = trim($body['orgDisplayName']);
            }
        }

        if ($errors !== [] || $tier === null) {
            throw new ValidationException($errors);
        }

        $requestId = $this->requestIdHolder->get();

        $output = $this->useCase->execute(new StartInstallSessionInput(
            tier: $tier,
            selectedApps: $selectedApps,
            orgDisplayName: $orgDisplayName,
            requestId: $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(
            InstallSessionView::toArray($output->session),
            201,
            ['Location' => '/api/v1/install-sessions/' . $output->session->id],
        );
    }

    /**
     * @param list<ValidationError> $errors
     *
     * @return list<string>
     */
    private function parseSelectedApps(mixed $value, array &$errors): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value) || !array_is_list($value)) {
            $errors[] = new ValidationError('selectedApps', 'selectedApps must be an array of catalog ids.', 'invalid_type');

            return [];
        }

        $apps = [];

        foreach ($value as $app) {
            if (!is_string($app)) {
                $errors[] = new ValidationError('selectedApps', 'selectedApps entries must be strings.', 'invalid_type');

                return [];
            }

            $apps[] = $app;
        }

        return $apps;
    }
}

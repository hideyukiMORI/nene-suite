<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseTargets;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeSuite\DatabaseProvision\DatabaseTargetMode;
use NeNeSuite\InstallSession\AppDatabaseTargetSelection;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;
use NeNeSuite\InstallSession\InstallSessionView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PUT /api/v1/install-sessions/{installSessionId}/database-targets — operationId setDatabaseTargets.
 *
 * Parses the transport shape (each entry's `catalogId` + `mode`, optional `server` / `name`)
 * into typed selections; semantic validation (app membership, safe name, external = adopt-only)
 * is the use case's job.
 */
final readonly class SetDatabaseTargetsHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private SetDatabaseTargetsUseCaseInterface $useCase,
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
        $targets = $this->parseTargets($body['targets'] ?? null);

        $requestId = $this->requestIdHolder->get();

        $output = $this->useCase->execute(new SetDatabaseTargetsInput(
            installSessionId: $id,
            targets: $targets,
            requestId: $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(InstallSessionView::toArray($output->session));
    }

    /**
     * @return list<AppDatabaseTargetSelection>
     */
    private function parseTargets(mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value)) {
            throw new ValidationException([
                new ValidationError('targets', 'targets must be an array of database target objects.', 'invalid_type'),
            ]);
        }

        $targets = [];

        foreach ($value as $index => $entry) {
            $field = "targets.{$index}";

            if (!is_array($entry)) {
                throw new ValidationException([
                    new ValidationError($field, 'Each target must be an object.', 'invalid_type'),
                ]);
            }

            $catalogId = $entry['catalogId'] ?? null;
            if (!is_string($catalogId) || $catalogId === '') {
                throw new ValidationException([
                    new ValidationError("{$field}.catalogId", 'catalogId is required.', 'required'),
                ]);
            }

            $modeValue = $entry['mode'] ?? null;
            if (!is_string($modeValue)) {
                throw new ValidationException([
                    new ValidationError("{$field}.mode", 'mode is required.', 'required'),
                ]);
            }

            $mode = DatabaseTargetMode::tryFrom($modeValue);
            if ($mode === null) {
                throw new ValidationException([
                    new ValidationError("{$field}.mode", 'mode must be one of: provision, adopt.', 'invalid_value'),
                ]);
            }

            $targets[] = new AppDatabaseTargetSelection(
                $catalogId,
                $mode,
                $this->optionalString($entry['server'] ?? null, "{$field}.server"),
                $this->optionalString($entry['name'] ?? null, "{$field}.name"),
            );
        }

        return $targets;
    }

    private function optionalString(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw new ValidationException([
                new ValidationError($field, "{$field} must be a string.", 'invalid_type'),
            ]);
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}

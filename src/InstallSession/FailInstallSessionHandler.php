<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /api/v1/install-sessions/{installSessionId}/fail — operationId failInstallSession.
 */
final readonly class FailInstallSessionHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    private const FAILURE_CODE_PATTERN = '/^[a-z0-9_]+$/';

    public function __construct(
        private FailInstallSessionUseCaseInterface $useCase,
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

        $failureCode = is_string($body['failureCode'] ?? null) ? trim((string) $body['failureCode']) : '';
        if (preg_match(self::FAILURE_CODE_PATTERN, $failureCode) !== 1) {
            throw new ValidationException([
                new ValidationError('failureCode', 'failureCode must match ^[a-z0-9_]+$.', 'invalid_format'),
            ]);
        }

        $reason = null;
        if (isset($body['reason'])) {
            if (!is_string($body['reason'])) {
                throw new ValidationException([
                    new ValidationError('reason', 'reason must be a string.', 'invalid_type'),
                ]);
            }
            $reason = trim($body['reason']);
        }

        $requestId = $this->requestIdHolder->get();

        $output = $this->useCase->execute(new FailInstallSessionInput(
            installSessionId: $id,
            failureCode: $failureCode,
            reason: $reason !== '' ? $reason : null,
            requestId: $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(InstallSessionView::toArray($output->session));
    }
}

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
 * POST /api/v1/install-sessions/{installSessionId}/disclaimer-acceptance — operationId acceptDisclaimer.
 */
final readonly class AcceptDisclaimerHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    public function __construct(
        private AcceptDisclaimerUseCaseInterface $useCase,
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

        $disclaimerVersion = is_string($body['disclaimerVersion'] ?? null) ? trim((string) $body['disclaimerVersion']) : '';
        if ($disclaimerVersion === '') {
            throw new ValidationException([
                new ValidationError('disclaimerVersion', 'disclaimerVersion is required.', 'required'),
            ]);
        }

        $acceptedLabel = null;
        if (isset($body['acceptedLabel'])) {
            if (!is_string($body['acceptedLabel'])) {
                throw new ValidationException([
                    new ValidationError('acceptedLabel', 'acceptedLabel must be a string.', 'invalid_type'),
                ]);
            }
            $acceptedLabel = trim($body['acceptedLabel']);
        }

        $requestId = $this->requestIdHolder->get();

        $output = $this->useCase->execute(new AcceptDisclaimerInput(
            installSessionId: $id,
            disclaimerVersion: $disclaimerVersion,
            acceptedLabel: $acceptedLabel !== '' ? $acceptedLabel : null,
            requestId: $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(InstallSessionView::toArray($output->session));
    }
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Deploy;

use Nene2\Http\JsonRequestBodyParser;
use Nene2\Http\JsonResponseFactory;
use Nene2\Log\RequestIdHolder;
use Nene2\Routing\Router;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PUT /api/v1/machine/deploy/requests/{id}/result — operationId reportDeployRequestResult.
 * Machine seam: authenticated by the deploy agent key.
 */
final readonly class ReportDeployRequestResultHandler
{
    private const ULID_PATTERN = '/^[0-9A-HJKMNP-TV-Z]{26}$/';

    private const MAX_DETAIL_LENGTH = 4000;

    public function __construct(
        private DeployAgentAuthenticator $authenticator,
        private ReportDeployResultUseCaseInterface $useCase,
        private JsonResponseFactory $response,
        private RequestIdHolder $requestIdHolder,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticator->ensure($request);

        $parameters = $request->getAttribute(Router::PARAMETERS_ATTRIBUTE, []);
        $id = is_array($parameters) && is_string($parameters['id'] ?? null) ? $parameters['id'] : '';

        if (preg_match(self::ULID_PATTERN, $id) !== 1) {
            throw new DeployRequestNotFoundException($id);
        }

        $body = JsonRequestBodyParser::parse($request);

        $rawStatus = is_string($body['status'] ?? null) ? $body['status'] : '';
        $status = DeployRequestStatus::tryFrom($rawStatus);

        if ($status === null || !$status->isTerminal()) {
            throw new ValidationException([
                new ValidationError('status', 'status must be succeeded or failed.', 'invalid'),
            ]);
        }

        $detail = is_string($body['detail'] ?? null) ? trim((string) $body['detail']) : null;

        if ($detail !== null && $detail === '') {
            $detail = null;
        }

        if ($detail !== null && mb_strlen($detail) > self::MAX_DETAIL_LENGTH) {
            throw new ValidationException([
                new ValidationError('detail', sprintf('detail must be at most %d characters.', self::MAX_DETAIL_LENGTH), 'too_long'),
            ]);
        }

        $requestId = $this->requestIdHolder->get();
        $output = $this->useCase->execute(new ReportDeployResultInput(
            $id,
            $status,
            $detail,
            $requestId !== '' ? $requestId : null,
        ));

        return $this->response->create(DeployRequestView::toArray($output->request));
    }
}

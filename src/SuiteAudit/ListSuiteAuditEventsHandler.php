<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

use Nene2\Http\JsonResponseFactory;
use Nene2\Validation\ValidationError;
use Nene2\Validation\ValidationException;
use NeNeSuite\Auth\BearerTokenAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /api/v1/suite-audit-events — operationId listSuiteAuditEvents.
 * Operator-authenticated; events are already sanitized at write time.
 */
final readonly class ListSuiteAuditEventsHandler
{
    private const ACTION_PATTERN = '/^[a-z_]+\.[a-z_]+$/';

    public function __construct(
        private BearerTokenAuthenticator $authenticator,
        private ListSuiteAuditEventsUseCaseInterface $useCase,
        private JsonResponseFactory $response,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->authenticator->operatorId($request);

        $params = $request->getQueryParams();

        $output = $this->useCase->execute(new ListSuiteAuditEventsInput(new SuiteAuditEventQuery(
            limit: $this->parseLimit($params['limit'] ?? null),
            cursor: $this->optionalString($params['cursor'] ?? null),
            installSessionId: $this->optionalString($params['installSessionId'] ?? null),
            action: $this->parseAction($params['action'] ?? null),
        )));

        return $this->response->create([
            'items' => array_map(
                static fn (SuiteAuditEvent $event): array => SuiteAuditEventView::toArray($event),
                $output->page->items,
            ),
            'nextCursor' => $output->page->nextCursor,
        ]);
    }

    private function parseLimit(mixed $value): int
    {
        if ($value === null || $value === '') {
            return SuiteAuditEventQuery::DEFAULT_LIMIT;
        }

        if (!is_string($value) || preg_match('/^\d+$/', $value) !== 1) {
            throw new ValidationException([
                new ValidationError('limit', 'limit must be a positive integer.', 'invalid_type'),
            ]);
        }

        return max(1, min((int) $value, SuiteAuditEventQuery::MAX_LIMIT));
    }

    private function parseAction(mixed $value): ?string
    {
        $action = $this->optionalString($value);

        if ($action !== null && preg_match(self::ACTION_PATTERN, $action) !== 1) {
            throw new ValidationException([
                new ValidationError('action', 'action must match {entity}.{verb}.', 'invalid_format'),
            ]);
        }

        return $action;
    }

    private function optionalString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}

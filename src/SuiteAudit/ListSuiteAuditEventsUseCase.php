<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

final readonly class ListSuiteAuditEventsUseCase implements ListSuiteAuditEventsUseCaseInterface
{
    public function __construct(
        private SuiteAuditEventReaderInterface $reader,
    ) {
    }

    public function execute(ListSuiteAuditEventsInput $input): ListSuiteAuditEventsOutput
    {
        return new ListSuiteAuditEventsOutput($this->reader->list($input->query));
    }
}

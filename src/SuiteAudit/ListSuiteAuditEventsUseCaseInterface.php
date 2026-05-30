<?php

declare(strict_types=1);

namespace NeNeSuite\SuiteAudit;

interface ListSuiteAuditEventsUseCaseInterface
{
    public function execute(ListSuiteAuditEventsInput $input): ListSuiteAuditEventsOutput;
}

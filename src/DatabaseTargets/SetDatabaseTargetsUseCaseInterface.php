<?php

declare(strict_types=1);

namespace NeNeSuite\DatabaseTargets;

use Nene2\Validation\ValidationException;
use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;

interface SetDatabaseTargetsUseCaseInterface
{
    /**
     * @throws InstallSessionNotFoundException when no session matches the id
     * @throws InstallSessionConflictException when the session is not in progress
     * @throws ValidationException             when a target names an unselected app, an unsafe database name, or provision-on-external (ADR 0021 OQ2)
     */
    public function execute(SetDatabaseTargetsInput $input): SetDatabaseTargetsOutput;
}

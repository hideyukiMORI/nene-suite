<?php

declare(strict_types=1);

namespace NeNeSuite\AppSelection;

use NeNeSuite\InstallSession\InstallSessionConflictException;
use NeNeSuite\InstallSession\InstallSessionNotFoundException;

interface UpdateAppSelectionUseCaseInterface
{
    /**
     * @throws InstallSessionNotFoundException when no session matches the id
     * @throws InstallSessionConflictException when the session is not in progress
     */
    public function execute(UpdateAppSelectionInput $input): UpdateAppSelectionOutput;
}

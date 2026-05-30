<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

interface FailInstallSessionUseCaseInterface
{
    /**
     * @throws InstallSessionNotFoundException when no session matches the id
     * @throws InstallSessionConflictException when the session is not in progress
     */
    public function execute(FailInstallSessionInput $input): FailInstallSessionOutput;
}

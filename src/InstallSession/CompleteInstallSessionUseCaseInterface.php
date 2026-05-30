<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

interface CompleteInstallSessionUseCaseInterface
{
    /**
     * @throws InstallSessionNotFoundException when no session matches the id
     * @throws InstallSessionConflictException when the session is not in progress
     * @throws InstallSessionNotReadyException when completion preconditions are unmet
     */
    public function execute(CompleteInstallSessionInput $input): CompleteInstallSessionOutput;
}

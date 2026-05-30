<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

interface GetInstallSessionUseCaseInterface
{
    /**
     * @throws InstallSessionNotFoundException when no session matches the id.
     */
    public function execute(GetInstallSessionInput $input): GetInstallSessionOutput;
}

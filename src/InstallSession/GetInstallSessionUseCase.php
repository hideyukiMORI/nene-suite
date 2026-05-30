<?php

declare(strict_types=1);

namespace NeNeSuite\InstallSession;

final readonly class GetInstallSessionUseCase implements GetInstallSessionUseCaseInterface
{
    public function __construct(
        private InstallSessionRepositoryInterface $sessions,
    ) {
    }

    public function execute(GetInstallSessionInput $input): GetInstallSessionOutput
    {
        $session = $this->sessions->findById($input->installSessionId);

        if ($session === null) {
            throw new InstallSessionNotFoundException($input->installSessionId);
        }

        return new GetInstallSessionOutput($session);
    }
}

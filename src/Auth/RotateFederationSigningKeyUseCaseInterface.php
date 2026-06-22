<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface RotateFederationSigningKeyUseCaseInterface
{
    public function execute(GenerateFederationSigningKeyInput $input): GenerateFederationSigningKeyOutput;
}

<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface GenerateFederationSigningKeyUseCaseInterface
{
    public function execute(GenerateFederationSigningKeyInput $input): GenerateFederationSigningKeyOutput;
}

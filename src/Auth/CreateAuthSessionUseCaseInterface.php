<?php

declare(strict_types=1);

namespace NeNeSuite\Auth;

interface CreateAuthSessionUseCaseInterface
{
    /**
     * @throws InvalidCredentialsException when the email is unknown or the password is wrong
     */
    public function execute(CreateAuthSessionInput $input): CreateAuthSessionOutput;
}
